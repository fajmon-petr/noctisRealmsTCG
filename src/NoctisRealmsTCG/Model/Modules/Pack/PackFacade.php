<?php declare(strict_types=1);

namespace App\Model\Modules\Pack;

use App\Model\Database\ManualTransaction;
use App\Model\Entity\Card;
use App\Model\Entity\Faction;
use App\Model\Entity\Player;
use App\Model\Entity\PlayerCard;
use App\Model\Entity\PlayerPack;
use App\Model\Entity\Rarity;
use App\Model\Service\Pack\PackGenerator;
use App\Model\Service\Pack\PackRules;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Nákup balíčků do batohu a jejich otevírání.
 *
 * Všechny kontroly proběhnou dřív, než se začnou měnit data – PackException tak nikdy
 * nenechá rozpracované změny. Transakce přes ManualTransaction (nezavírá EntityManager).
 */
class PackFacade
{
    use ManualTransaction;

    public function __construct(
        private EntityManagerInterface $em,
        private PackGenerator $generator,
    ) {}

    /**
     * Koupí balíčky dané frakce do batohu hráče.
     *
     * @return list<PlayerPack>
     * @throws PackException
     */
    public function buy(Player $player, Faction $faction, int $count = 1): array
    {
        if ($count < 1 || $count > PackRules::MAX_BUY_AT_ONCE) {
            throw new PackException('Najednou lze koupit 1 až ' . PackRules::MAX_BUY_AT_ONCE . ' balíčků.');
        }
        if (!$faction->isSelectable()) {
            throw new PackException("Balíčky frakce {$faction->getName()} nelze koupit.");
        }

        return $this->transactional(function () use ($player, $faction, $count): array {
            $this->em->refresh($player, LockMode::PESSIMISTIC_WRITE); // aktuální MD + zámek proti dvojímu utracení

            $price = PackRules::PRICE * $count;
            if ($player->getMoonDust() < $price) {
                throw new PackException("Nemáš dost Moon Dustu – potřebuješ $price, máš {$player->getMoonDust()}.");
            }

            $player->addMoonDust(-$price);

            $packs = [];
            for ($i = 0; $i < $count; $i++) {
                $this->em->persist($packs[] = new PlayerPack($player, $faction));
            }
            return $packs;
        });
    }

    /**
     * Otevře balíček z batohu: vylosuje karty, přidá je do kolekce a posune počítadlo garancí.
     *
     * @throws PackException
     */
    public function open(Player $player, PlayerPack $pack): PlayerPack
    {
        return $this->transactional(function () use ($player, $pack): PlayerPack {
            $this->em->refresh($player, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($pack, LockMode::PESSIMISTIC_WRITE); // zámek proti dvojímu otevření

            if ($pack->getPlayer()->getId() !== $player->getId()) {
                throw new PackException('Tento balíček ti nepatří.');
            }
            if ($pack->isOpened()) {
                throw new PackException('Balíček už je otevřený.');
            }

            $pool = $this->loadPool($pack->getFaction());
            if ($pool === []) {
                throw new PackException("Pro frakci {$pack->getFaction()->getName()} zatím nejsou žádné karty.");
            }

            // --- od tady se mění data ---
            $openNumber = $player->getOpenedPacks() + 1;
            $player->setOpenedPacks($openNumber);

            $guarantee = PackRules::guaranteeFor($openNumber);
            $pack->open($openNumber, $guarantee !== null ? $this->em->find(Rarity::class, $guarantee) : null);

            $collection = [];
            foreach ($this->generator->generate($openNumber, $this->getBaseChances()) as $i => $slot) {
                $card = $this->pickCard($pool, $slot->rarity, $slot->guaranteed);
                $pack->addCard($card, $i + 1, $slot->guaranteed);
                $this->addToCollection($player, $card, $collection);
            }

            $player->setCards($player->getCards() + PackRules::CARDS_PER_PACK);

            return $pack;
        });
    }

    /**
     * Otevře až `count` nejstarších balíčků z batohu (jen dané frakce, nebo všech při null).
     * Každý balíček se otevírá ve vlastní transakci. Když otevírání selže až po prvním balíčku,
     * vrátí se ty, které se otevřít podařily (volající porovná počet s požadavkem).
     *
     * @return list<PlayerPack> otevřené balíčky v pořadí otevření
     * @throws PackException když nejde otevřít ani jeden
     */
    public function openMany(Player $player, ?Faction $faction, int $count): array
    {
        if ($count < 1 || $count > PackRules::MAX_OPEN_AT_ONCE) {
            throw new PackException('Najednou lze otevřít 1 až ' . PackRules::MAX_OPEN_AT_ONCE . ' balíčků.');
        }

        $packs = array_slice($this->getBackpack($player, $faction), 0, $count);
        if ($packs === []) {
            throw new PackException('V batohu nemáš žádný takový balíček.');
        }

        $opened = [];
        foreach ($packs as $pack) {
            try {
                $opened[] = $this->open($player, $pack);
            } catch (PackException $e) {
                if ($opened === []) {
                    throw $e;
                }
                break;
            }
        }
        return $opened;
    }

    /**
     * Neotevřené balíčky hráče (volitelně jen jedné frakce), nejstarší první.
     *
     * @return list<PlayerPack>
     */
    public function getBackpack(Player $player, ?Faction $faction = null): array
    {
        $criteria = ['player' => $player, 'openedAt' => null];
        if ($faction !== null) {
            $criteria['faction'] = $faction;
        }
        return $this->em->getRepository(PlayerPack::class)->findBy($criteria, ['purchasedAt' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * Počty neotevřených balíčků podle frakce.
     *
     * @return array<string, array{faction: Faction, count: int}> slug frakce => frakce + počet
     */
    public function getBackpackSummary(Player $player): array
    {
        $summary = [];
        foreach ($this->getBackpack($player) as $pack) {
            $slug = $pack->getFaction()->getSlug();
            $summary[$slug] ??= ['faction' => $pack->getFaction(), 'count' => 0];
            $summary[$slug]['count']++;
        }
        return $summary;
    }

    /** Balíček hráče podle id (null, pokud neexistuje nebo patří jinému hráči) */
    public function getPlayerPack(Player $player, int $packId): ?PlayerPack
    {
        return $this->em->getRepository(PlayerPack::class)->findOneBy(['id' => $packId, 'player' => $player]);
    }

    /** @return array<string, float> kód rarity => základní šance (%) z tabulky `rarity` */
    public function getBaseChances(): array
    {
        $chances = [];
        foreach ($this->em->getRepository(Rarity::class)->findBy([], ['sortOrder' => 'ASC']) as $rarity) {
            $chances[$rarity->getCode()] = $rarity->getDropChance();
        }
        return $chances;
    }

    /**
     * Karty, které mohou padnout v balíčku frakce: karty frakce + neutrální.
     *
     * @return array<string, non-empty-list<Card>> kód rarity => karty
     */
    private function loadPool(Faction $faction): array
    {
        /** @var list<Card> $cards */
        $cards = $this->em->createQueryBuilder()
            ->select('c', 'r')
            ->from(Card::class, 'c')
            ->join('c.rarity', 'r')
            ->join('c.faction', 'f')
            ->where('f = :faction OR f.slug = :neutral')
            ->setParameter('faction', $faction)
            ->setParameter('neutral', Faction::NEUTRAL_SLUG)
            ->orderBy('c.id')
            ->getQuery()
            ->getResult();

        $pool = [];
        foreach ($cards as $card) {
            $pool[$card->getRarity()->getCode()][] = $card;
        }
        return $pool;
    }

    /**
     * Náhodná karta vylosované rarity. Když karta té rarity v poolu chybí, vezme se nejbližší
     * nižší rarita (u garantovaného slotu nejdřív nejbližší vyšší, aby garance neklesla).
     *
     * @param array<string, non-empty-list<Card>> $pool
     */
    private function pickCard(array $pool, string $rarity, bool $guaranteed): Card
    {
        $order = [Rarity::COMMON, Rarity::UNCOMMON, Rarity::RARE, Rarity::EPIC, Rarity::LEGENDARY];
        $index = (int) array_search($rarity, $order, true);

        $lower = array_reverse(array_slice($order, 0, $index));
        $higher = array_slice($order, $index + 1);
        $candidates = [$rarity, ...($guaranteed ? [...$higher, ...$lower] : [...$lower, ...$higher])];

        foreach ($candidates as $code) {
            if (isset($pool[$code])) {
                return $this->generator->pickRandom($pool[$code]);
            }
        }

        throw new \LogicException('Prázdný pool karet – má se zkontrolovat před losováním.');
    }

    /** @param array<int, PlayerCard|null> $collection karty v kolekci dotčené tímto otevřením (i ještě neuložené) */
    private function addToCollection(Player $player, Card $card, array &$collection): void
    {
        $owned = $collection[$card->getId()]
            ??= $this->em->getRepository(PlayerCard::class)->findOneBy(['player' => $player, 'card' => $card]);

        if ($owned !== null) {
            $owned->add();
            return;
        }

        $this->em->persist($collection[$card->getId()] = new PlayerCard($player, $card));
    }
}
