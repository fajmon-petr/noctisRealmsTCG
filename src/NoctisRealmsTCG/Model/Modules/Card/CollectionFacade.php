<?php declare(strict_types=1);

namespace App\Model\Modules\Card;

use App\Model\Database\ManualTransaction;
use App\Model\Entity\Card;
use App\Model\Entity\Player;
use App\Model\Entity\PlayerCard;
use App\Model\Modules\Faction\FactionStatsFacade;
use App\Model\Modules\Player\SeasonStatsFacade;
use App\Model\Modules\Season\SeasonFacade;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Kolekce karet hráče a darování karet frakci.
 *
 * Darování: hráč dostane Moon Dust podle hodnoty rarity (`rarity.donation_value`) a stejný počet
 * bodů se připíše jemu i jeho frakci do probíhající sezóny. Darovat jde jen duplikáty
 * (poslední kus zůstává) a vždy jen do VLASTNÍ frakce hráče – frakce se nebere z požadavku.
 */
class CollectionFacade
{
    use ManualTransaction;

    public function __construct(
        private EntityManagerInterface $em,
        private SeasonFacade $seasonFacade,
        private SeasonStatsFacade $seasonStatsFacade,
        private FactionStatsFacade $factionStatsFacade,
    ) {}

    /**
     * Karty v kolekci hráče – od nejvzácnějších, pak podle názvu.
     *
     * @return list<PlayerCard>
     */
    public function getCollection(Player $player): array
    {
        return $this->em->createQueryBuilder()
            ->select('pc', 'c', 'r', 'f')
            ->from(PlayerCard::class, 'pc')
            ->join('pc.card', 'c')
            ->join('c.rarity', 'r')
            ->leftJoin('c.faction', 'f')
            ->where('pc.player = :player')
            ->setParameter('player', $player)
            ->orderBy('r.sortOrder', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Daruje `count` kusů karty frakci hráče.
     *
     * @throws DonationException
     */
    public function donate(Player $player, Card $card, int $count = 1): DonationResult
    {
        if ($count < 1) {
            throw new DonationException('Daruj alespoň jednu kartu.');
        }

        return $this->transactional(function () use ($player, $card, $count): DonationResult {
            $this->em->refresh($player, LockMode::PESSIMISTIC_WRITE); // zámek proti souběžnému darování

            $faction = $player->getFaction();
            if ($faction === null) {
                throw new DonationException('Nejdřív si vyber frakci.');
            }

            $season = $this->seasonFacade->getCurrentSeason();
            if ($season === null) {
                throw new DonationException('Právě neprobíhá žádná sezóna, darovat teď nejde.');
            }

            /** @var PlayerCard|null $owned */
            $owned = $this->em->getRepository(PlayerCard::class)->findOneBy(['player' => $player, 'card' => $card]);
            if ($owned === null) {
                throw new DonationException('Tuto kartu nemáš v kolekci.');
            }
            $this->em->refresh($owned, LockMode::PESSIMISTIC_WRITE);

            if ($count > $owned->getDonatableQuantity()) {
                throw new DonationException(sprintf(
                    'Kartu %s můžeš darovat nejvýš %d× – poslední kus si vždy necháváš.',
                    $card->getName(),
                    $owned->getDonatableQuantity(),
                ));
            }

            // --- od tady se mění data ---
            $rarity = $card->getRarity();
            $value = $rarity->getDonationValue() * $count;

            $owned->remove($count);
            $player->addMoonDust($value);
            $player->setCards($player->getCards() - $count);

            $cards = [$rarity->getCode() => $count];
            $this->seasonStatsFacade->addContribution((int) $player->getId(), $faction->getId(), $season->getId(), $value, $cards);
            $this->factionStatsFacade->addContribution($faction->getId(), $season->getId(), $value, $cards);

            return new DonationResult($card, $count, $faction, $value, $value);
        });
    }
}
