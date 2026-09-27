<?php declare(strict_types=1);

/**
 * Test: App\Model\Modules\Card\CollectionFacade (integrační, DB noctis_test)
 */

use App\Model\Entity\Card;
use App\Model\Entity\Player;
use App\Model\Entity\PlayerCard;
use App\Model\Entity\Rarity;
use App\Model\Entity\Season;
use App\Model\Modules\Card\CollectionFacade;
use App\Model\Modules\Card\DonationException;
use App\Model\Modules\Season\SeasonFacade;
use Tester\Assert;
use Tests\Support\IntegrationTestCase;

require __DIR__ . '/../../../bootstrap.php';


final class CollectionFacadeTest extends IntegrationTestCase
{
    private CollectionFacade $facade;


    protected function setUp(): void
    {
        parent::setUp();
        $this->facade = $this->getService(CollectionFacade::class);
    }


    /** Dá hráči `quantity` kusů karty do kolekce */
    private function giveCard(Player $player, Card $card, int $quantity): PlayerCard
    {
        $owned = new PlayerCard($player, $card);
        if ($quantity > 1) {
            $owned->add($quantity - 1);
        }
        $player->setCards($player->getCards() + $quantity);
        $this->em->persist($owned);
        $this->em->flush();
        return $owned;
    }


    private function currentSeason(): Season
    {
        return $this->getService(SeasonFacade::class)->getCurrentSeason()
            ?? throw new LogicException('noctis_test nemá probíhající sezónu – spusť seedy.');
    }


    /** @return array<string, mixed>|null */
    private function playerStats(Player $player): ?array
    {
        return $this->em->getConnection()->fetchAssociative(
            'SELECT * FROM player_season_stat WHERE player_id = ? AND season_id = ?',
            [$player->getId(), $this->currentSeason()->getId()],
        ) ?: null;
    }


    /** @return array<string, mixed>|null */
    private function factionStats(string $factionSlug): ?array
    {
        return $this->em->getConnection()->fetchAssociative(
            'SELECT * FROM faction_season_stat WHERE faction_id = ? AND season_id = ?',
            [$this->getFaction($factionSlug)->getId(), $this->currentSeason()->getId()],
        ) ?: null;
    }


    public function testDarovaniDaPrachAOdebereKusy(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 10);
        $card = $this->createCard(Rarity::RARE, 'ignis');
        $owned = $this->giveCard($player, $card, 4);

        $result = $this->facade->donate($player, $card, 2);

        $value = 2 * $card->getRarity()->getDonationValue();
        Assert::same(2, $result->count);
        Assert::same($value, $result->moonDust);
        Assert::same($value, $result->points);
        Assert::same('ignis', $result->faction->getSlug());
        Assert::same(10 + $value, $player->getMoonDust());
        Assert::same(2, $owned->getQuantity());
        Assert::same(2, $player->getCards());
    }


    public function testDarovaniPripiseBodyHraciIFrakci(): void
    {
        $player = $this->createPlayer('vitae');
        $epic = $this->createCard(Rarity::EPIC, 'vitae');
        $this->giveCard($player, $epic, 3);

        $this->facade->donate($player, $epic, 2);

        $value = 2 * $epic->getRarity()->getDonationValue();
        $stats = $this->playerStats($player);
        Assert::same($value, (int) $stats['points_total']);
        Assert::same(2, (int) $stats['epic']);
        Assert::same(0, (int) $stats['common']);
        Assert::same($this->getFaction('vitae')->getId(), (int) $stats['faction_id']);

        $faction = $this->factionStats('vitae');
        Assert::true((int) $faction['points_total'] >= $value);
        Assert::true((int) $faction['epic'] >= 2);
    }


    public function testOpakovaneDarovaniSeSčítá(): void
    {
        $player = $this->createPlayer('noctis');
        $common = $this->createCard(Rarity::COMMON, 'noctis');
        $rare = $this->createCard(Rarity::RARE, 'noctis');
        $this->giveCard($player, $common, 5);
        $this->giveCard($player, $rare, 3);
        $factionBefore = (int) ($this->factionStats('noctis')['points_total'] ?? 0);

        $this->facade->donate($player, $common, 2);
        $this->facade->donate($player, $rare, 1);
        $this->facade->donate($player, $common, 1);

        $expected = 3 * $common->getRarity()->getDonationValue() + $rare->getRarity()->getDonationValue();
        $stats = $this->playerStats($player);
        Assert::same($expected, (int) $stats['points_total']);
        Assert::same(3, (int) $stats['common']);
        Assert::same(1, (int) $stats['rare']);
        Assert::same($factionBefore + $expected, (int) $this->factionStats('noctis')['points_total']);
    }


    public function testPosledniKusNelzeDarovat(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 10);
        $card = $this->createCard(Rarity::LEGENDARY, 'ignis');
        $owned = $this->giveCard($player, $card, 1);

        Assert::exception(fn() => $this->facade->donate($player, $card), DonationException::class, '~nejvýš 0×~');
        Assert::same(1, $owned->getQuantity());
        Assert::same(10, $player->getMoonDust());
        Assert::null($this->playerStats($player));
        Assert::true($this->em->isOpen());
    }


    public function testNelzeDarovatVicNezDuplikaty(): void
    {
        $player = $this->createPlayer('ignis');
        $card = $this->createCard(Rarity::COMMON, 'ignis');
        $owned = $this->giveCard($player, $card, 3);

        Assert::exception(fn() => $this->facade->donate($player, $card, 3), DonationException::class, '~nejvýš 2×~');
        Assert::same(3, $owned->getQuantity());

        $this->facade->donate($player, $card, 2); // 2 jde
        Assert::same(1, $owned->getQuantity());
    }


    public function testKartuMimoKolekciNelzeDarovat(): void
    {
        $player = $this->createPlayer('ignis');
        $card = $this->createCard(Rarity::RARE, 'ignis');

        Assert::exception(fn() => $this->facade->donate($player, $card), DonationException::class, 'Tuto kartu nemáš v kolekci.');
    }


    public function testCiziKartuNelzeDarovat(): void
    {
        $owner = $this->createPlayer('ignis');
        $other = $this->createPlayer('ignis');
        $card = $this->createCard(Rarity::RARE, 'ignis');
        $this->giveCard($owner, $card, 5);

        Assert::exception(fn() => $this->facade->donate($other, $card), DonationException::class, 'Tuto kartu nemáš v kolekci.');
    }


    public function testNeplatnyPocet(): void
    {
        $player = $this->createPlayer('ignis');
        $card = $this->createCard(Rarity::COMMON, 'ignis');
        $this->giveCard($player, $card, 5);

        Assert::exception(fn() => $this->facade->donate($player, $card, 0), DonationException::class);
        Assert::exception(fn() => $this->facade->donate($player, $card, -2), DonationException::class);
    }


    public function testHracBezFrakceNemuzeDarovat(): void
    {
        $player = $this->createPlayer(null);
        $card = $this->createCard(Rarity::COMMON, 'ignis');
        $this->giveCard($player, $card, 3);

        Assert::exception(fn() => $this->facade->donate($player, $card), DonationException::class, 'Nejdřív si vyber frakci.');
    }


    public function testBodyJdouVzdyVlastniFrakci(): void
    {
        // hráč Ignis daruje kartu Vitae → body dostane Ignis, ne Vitae
        $player = $this->createPlayer('ignis');
        $vitaeCard = $this->createCard(Rarity::EPIC, 'vitae');
        $this->giveCard($player, $vitaeCard, 2);
        $vitaeBefore = (int) ($this->factionStats('vitae')['points_total'] ?? 0);
        $ignisBefore = (int) ($this->factionStats('ignis')['points_total'] ?? 0);

        $result = $this->facade->donate($player, $vitaeCard);

        Assert::same('ignis', $result->faction->getSlug());
        Assert::same($ignisBefore + $result->points, (int) $this->factionStats('ignis')['points_total']);
        Assert::same($vitaeBefore, (int) ($this->factionStats('vitae')['points_total'] ?? 0));
    }


    public function testBezProbihajiciSezonyNelzeDarovat(): void
    {
        // ukončíme všechny sezóny (jen v transakci testu)
        $this->em->createQuery('UPDATE ' . Season::class . ' s SET s.endAt = :past WHERE s.endAt IS NULL OR s.endAt > :past')
            ->setParameter('past', new DateTimeImmutable('-1 day'))
            ->execute();

        $player = $this->createPlayer('ignis', moonDust: 10);
        $card = $this->createCard(Rarity::COMMON, 'ignis');
        $owned = $this->giveCard($player, $card, 3);

        Assert::exception(fn() => $this->facade->donate($player, $card), DonationException::class, '~neprobíhá žádná sezóna~');
        Assert::same(3, $owned->getQuantity());
        Assert::same(10, $player->getMoonDust());
    }


    public function testKolekceOdNejvzacnejsich(): void
    {
        $player = $this->createPlayer('ignis');
        $this->giveCard($player, $this->createCard(Rarity::COMMON, 'ignis', 'A common'), 3);
        $this->giveCard($player, $this->createCard(Rarity::LEGENDARY, 'ignis', 'Z legendary'), 1);
        $this->giveCard($player, $this->createCard(Rarity::RARE, 'ignis', 'M rare'), 2);

        $codes = array_map(fn(PlayerCard $c) => $c->getCard()->getRarity()->getCode(), $this->facade->getCollection($player));

        Assert::same([Rarity::LEGENDARY, Rarity::RARE, Rarity::COMMON], $codes);
    }
}


(new CollectionFacadeTest)->run();
