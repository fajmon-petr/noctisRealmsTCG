<?php declare(strict_types=1);

/**
 * Test: App\Model\Modules\Pack\PackFacade (integrační, DB noctis_test)
 */

use App\Model\Entity\Card;
use App\Model\Entity\Faction;
use App\Model\Entity\Player;
use App\Model\Entity\PlayerCard;
use App\Model\Entity\PlayerPack;
use App\Model\Entity\PlayerPackCard;
use App\Model\Entity\Rarity;
use App\Model\Modules\Pack\PackException;
use App\Model\Modules\Pack\PackFacade;
use App\Model\Service\Pack\PackRules;
use Tester\Assert;
use Tests\Support\IntegrationTestCase;

require __DIR__ . '/../../../bootstrap.php';


final class PackFacadeTest extends IntegrationTestCase
{
    private const ALL_RARITIES = [Rarity::COMMON, Rarity::UNCOMMON, Rarity::RARE, Rarity::EPIC, Rarity::LEGENDARY];

    private PackFacade $facade;


    protected function setUp(): void
    {
        parent::setUp();
        $this->facade = $this->getService(PackFacade::class);
    }


    /** Jedna karta od každé rarity pro frakci */
    private function createFullPool(string $factionSlug): void
    {
        foreach (self::ALL_RARITIES as $rarity) {
            $this->createCard($rarity, $factionSlug, "$factionSlug $rarity");
        }
    }


    private function openedPack(Player $player, string $factionSlug, int $alreadyOpened = 0): PlayerPack
    {
        $player->setOpenedPacks($alreadyOpened);
        $player->setMoonDust(PackRules::PRICE);
        $this->em->flush();

        [$pack] = $this->facade->buy($player, $this->getFaction($factionSlug));
        return $this->facade->open($player, $pack);
    }


    /** @return list<string> kódy rarit karet v balíčku podle slotů */
    private function rarities(PlayerPack $pack): array
    {
        return array_values(array_map(
            fn(PlayerPackCard $c) => $c->getCard()->getRarity()->getCode(),
            $pack->getCards()->toArray(),
        ));
    }


    // --- nákup ---

    public function testNakupOdectePrachAPridaBalickyDoBatohu(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 350);

        $packs = $this->facade->buy($player, $this->getFaction('vitae'), 3);

        Assert::count(3, $packs);
        Assert::same(50, $player->getMoonDust());
        Assert::count(3, $this->facade->getBackpack($player));
        foreach ($this->facade->getBackpack($player) as $pack) {
            Assert::same('vitae', $pack->getFaction()->getSlug());
            Assert::false($pack->isOpened());
        }
    }


    public function testNakupSeUloziDoDb(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 100);
        $this->facade->buy($player, $this->getFaction('ignis'));

        $this->em->clear();
        $reloaded = $this->em->find(Player::class, $player->getId());

        Assert::same(0, $reloaded?->getMoonDust());
        Assert::count(1, $this->em->getRepository(PlayerPack::class)->findBy(['player' => $reloaded]));
    }


    public function testNedostatekPrachuNicNezmeni(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 150);

        Assert::exception(
            fn() => $this->facade->buy($player, $this->getFaction('ignis'), 2),
            PackException::class,
            '~potřebuješ 200, máš 150~',
        );
        Assert::same(150, $player->getMoonDust());
        Assert::count(0, $this->facade->getBackpack($player));
        Assert::true($this->em->isOpen(), 'EntityManager musí zůstat použitelný');
    }


    public function testNeutralniBalicekNelzeKoupit(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 500);

        Assert::exception(
            fn() => $this->facade->buy($player, $this->getFaction(Faction::NEUTRAL_SLUG)),
            PackException::class,
        );
        Assert::same(500, $player->getMoonDust());
    }


    public function testNeplatnyPocetBalicku(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 5000);
        $faction = $this->getFaction('ignis');

        Assert::exception(fn() => $this->facade->buy($player, $faction, 0), PackException::class);
        Assert::exception(fn() => $this->facade->buy($player, $faction, PackRules::MAX_BUY_AT_ONCE + 1), PackException::class);
        Assert::same(5000, $player->getMoonDust());
    }


    // --- otevření ---

    public function testOtevreniDa5KaretDoKolekce(): void
    {
        $this->createFullPool('noctis');
        $player = $this->createPlayer('noctis');

        $pack = $this->openedPack($player, 'noctis');

        Assert::true($pack->isOpened());
        Assert::same(1, $pack->getOpenNumber());
        Assert::count(PackRules::CARDS_PER_PACK, $pack->getCards());
        Assert::same(1, $player->getOpenedPacks());
        Assert::same(PackRules::CARDS_PER_PACK, $player->getCards());
        Assert::count(0, $this->facade->getBackpack($player));

        // kolekce: součet kusů = 5, stejná karta je v kolekci jen jednou
        $owned = $this->em->getRepository(PlayerCard::class)->findBy(['player' => $player]);
        Assert::same(PackRules::CARDS_PER_PACK, array_sum(array_map(fn(PlayerCard $c) => $c->getQuantity(), $owned)));
        Assert::same(count($owned), count(array_unique(array_map(fn(PlayerCard $c) => $c->getCard()->getId(), $owned))));
    }


    public function testStejnaKartaZvysiPocetKusu(): void
    {
        $this->createCard(Rarity::COMMON, 'ignis', 'Jediná karta'); // pool = 1 karta → všech 5 slotů stejná karta
        $player = $this->createPlayer('ignis');

        $this->openedPack($player, 'ignis');

        $owned = $this->em->getRepository(PlayerCard::class)->findBy(['player' => $player]);
        Assert::count(1, $owned);
        Assert::same(5, $owned[0]->getQuantity());
    }


    public function testCiziBalicekNelzeOtevrit(): void
    {
        $this->createFullPool('ignis');
        $owner = $this->createPlayer('ignis', moonDust: 100);
        $thief = $this->createPlayer('ignis');
        [$pack] = $this->facade->buy($owner, $this->getFaction('ignis'));

        Assert::exception(fn() => $this->facade->open($thief, $pack), PackException::class, 'Tento balíček ti nepatří.');
        Assert::false($pack->isOpened());
        Assert::same(0, $thief->getOpenedPacks());
    }


    public function testBalicekNelzeOtevritDvakrat(): void
    {
        $this->createFullPool('ignis');
        $player = $this->createPlayer('ignis');
        $pack = $this->openedPack($player, 'ignis');

        Assert::exception(fn() => $this->facade->open($player, $pack), PackException::class, 'Balíček už je otevřený.');
        Assert::same(1, $player->getOpenedPacks());
        Assert::same(PackRules::CARDS_PER_PACK, $player->getCards());
    }


    public function testPrazdnyPoolNicNezmeni(): void
    {
        // pool vitae = karty vitae + neutrální; smažeme je (jen v transakci testu, rollback je vrátí)
        $this->em->createQuery('DELETE FROM ' . Card::class . ' c WHERE c.faction IN (:factions)')
            ->setParameter('factions', [$this->getFaction('vitae'), $this->getFaction(Faction::NEUTRAL_SLUG)])
            ->execute();
        $player = $this->createPlayer('vitae', moonDust: 100);
        [$pack] = $this->facade->buy($player, $this->getFaction('vitae'));

        Assert::exception(fn() => $this->facade->open($player, $pack), PackException::class);
        Assert::false($pack->isOpened());
        Assert::same(0, $player->getOpenedPacks());
        Assert::count(1, $this->facade->getBackpack($player));
    }


    public function testKartyJenZFrakceBalickuANeutralni(): void
    {
        $this->createFullPool('ignis');
        $this->createFullPool(Faction::NEUTRAL_SLUG);
        $this->createFullPool('vitae');
        $player = $this->createPlayer('ignis', moonDust: 1000);

        foreach ($this->facade->buy($player, $this->getFaction('ignis'), 10) as $pack) {
            $this->facade->open($player, $pack);
            foreach ($pack->getCards() as $packCard) {
                Assert::contains($packCard->getCard()->getFaction()?->getSlug(), ['ignis', Faction::NEUTRAL_SLUG]);
            }
        }
        Assert::same(10, $player->getOpenedPacks());
    }


    // --- otevření více balíčků ---

    public function testOpenManyOtevreNejstarsiBalickyFrakce(): void
    {
        $this->createFullPool('ignis');
        $this->createFullPool('vitae');
        $player = $this->createPlayer('ignis', moonDust: 1000);
        $ignis = $this->facade->buy($player, $this->getFaction('ignis'), 3);
        $this->facade->buy($player, $this->getFaction('vitae'), 2);

        $opened = $this->facade->openMany($player, $this->getFaction('ignis'), 2);

        Assert::same([$ignis[0]->getId(), $ignis[1]->getId()], array_map(fn(PlayerPack $p) => $p->getId(), $opened));
        Assert::same([1, 2], array_map(fn(PlayerPack $p) => $p->getOpenNumber(), $opened));
        Assert::same(['ignis' => 1, 'vitae' => 2], array_map(fn(array $g) => $g['count'], $this->facade->getBackpackSummary($player)));
    }


    public function testOpenManyVsechnyFrakceVPoradiNakupu(): void
    {
        $this->createFullPool('ignis');
        $this->createFullPool('vitae');
        $player = $this->createPlayer('ignis', moonDust: 1000);
        $this->facade->buy($player, $this->getFaction('vitae'));
        $this->facade->buy($player, $this->getFaction('ignis'), 2);

        $opened = $this->facade->openMany($player, null, 3);

        Assert::same(['vitae', 'ignis', 'ignis'], array_map(fn(PlayerPack $p) => $p->getFaction()->getSlug(), $opened));
        Assert::same(3, $player->getOpenedPacks());
        Assert::same([], $this->facade->getBackpackSummary($player));
    }


    public function testOpenManyVicNezJeVBatohuOtevreCoJe(): void
    {
        $this->createFullPool('ignis');
        $player = $this->createPlayer('ignis', moonDust: 200);
        $this->facade->buy($player, $this->getFaction('ignis'), 2);

        Assert::count(2, $this->facade->openMany($player, $this->getFaction('ignis'), 10));
        Assert::same(2 * PackRules::CARDS_PER_PACK, $player->getCards());
    }


    public function testOpenManyNeplatnyPocetAPrazdnyBatoh(): void
    {
        $this->createFullPool('ignis');
        $player = $this->createPlayer('ignis', moonDust: 100);

        Assert::exception(fn() => $this->facade->openMany($player, null, 1), PackException::class, 'V batohu nemáš žádný takový balíček.');

        $this->facade->buy($player, $this->getFaction('ignis'));
        Assert::exception(fn() => $this->facade->openMany($player, null, 0), PackException::class);
        Assert::exception(fn() => $this->facade->openMany($player, null, PackRules::MAX_OPEN_AT_ONCE + 1), PackException::class);
        Assert::same(0, $player->getOpenedPacks());
    }


    // --- garance ---

    public function testBezGaranceZadnyGarantovanySlot(): void
    {
        $this->createFullPool('ignis');
        $pack = $this->openedPack($this->createPlayer('ignis'), 'ignis', alreadyOpened: 0);

        Assert::null($pack->getGuarantee());
        foreach ($pack->getCards() as $card) {
            Assert::false($card->isGuaranteed());
        }
    }


    public function testPatyBalicekMaGaranciRarePlus(): void
    {
        $this->createFullPool('ignis');
        $pack = $this->openedPack($this->createPlayer('ignis'), 'ignis', alreadyOpened: 4);

        Assert::same(5, $pack->getOpenNumber());
        Assert::same(Rarity::RARE, $pack->getGuarantee()?->getCode());
        $last = $pack->getCards()->last();
        Assert::true($last->isGuaranteed());
        Assert::contains($last->getCard()->getRarity()->getCode(), [Rarity::RARE, Rarity::EPIC]);
    }


    public function testOtevrenyBalicekJdeZnovuNacist(): void
    {
        // stránka výsledku načítá balíček v novém požadavku → garance je lazy proxy Rarity
        $this->createFullPool('ignis');
        $player = $this->createPlayer('ignis');
        $pack = $this->openedPack($player, 'ignis', alreadyOpened: 4);
        $this->em->clear();

        $player = $this->em->find(Player::class, $player->getId());
        $reloaded = $this->facade->getPlayerPack($player, $pack->getId());

        Assert::same('Rare', $reloaded?->getGuarantee()?->getName());
        Assert::count(PackRules::CARDS_PER_PACK, $reloaded->getCards());
        foreach ($reloaded->getCards() as $packCard) {
            Assert::type('string', $packCard->getCard()->getRarity()->getName());
            Assert::type('string', $packCard->getCard()->getFaction()?->getName());
        }
    }


    public function testDesatyBalicekMaGaranciEpic(): void
    {
        $this->createFullPool('ignis');
        $pack = $this->openedPack($this->createPlayer('ignis'), 'ignis', alreadyOpened: 9);

        Assert::same(Rarity::EPIC, $pack->getGuarantee()?->getCode());
        Assert::same(Rarity::EPIC, $this->rarities($pack)[4]);
    }


    public function testDvacatyBalicekMaLegendary(): void
    {
        $this->createFullPool('ignis');
        $pack = $this->openedPack($this->createPlayer('ignis'), 'ignis', alreadyOpened: 19);

        Assert::same(Rarity::LEGENDARY, $pack->getGuarantee()?->getCode());
        Assert::same(Rarity::LEGENDARY, $this->rarities($pack)[4]);
    }


    public function testGaranceBezKartyTeRarityVezmeVyssi(): void
    {
        // pool bez epic → garance epic+ musí dát legendary (vyšší), ne rare (nižší)
        foreach ([Rarity::COMMON, Rarity::UNCOMMON, Rarity::RARE, Rarity::LEGENDARY] as $rarity) {
            $this->createCard($rarity, 'ignis');
        }
        $pack = $this->openedPack($this->createPlayer('ignis'), 'ignis', alreadyOpened: 9);

        Assert::same(Rarity::LEGENDARY, $this->rarities($pack)[4]);
    }


    public function testBeznySlotBezKartyTeRarityVezmeNizsi(): void
    {
        // pool jen common + legendary → běžné sloty dají common (vylosovaná U/R/E klesne na common)
        $this->createCard(Rarity::COMMON, 'ignis');
        $this->createCard(Rarity::LEGENDARY, 'ignis');
        $player = $this->createPlayer('ignis', moonDust: 1000);

        $counts = [];
        foreach ($this->facade->buy($player, $this->getFaction('ignis'), 4) as $pack) { // balíčky 1–4, bez garance
            $this->facade->open($player, $pack);
            foreach ($this->rarities($pack) as $rarity) {
                $counts[$rarity] = ($counts[$rarity] ?? 0) + 1;
            }
        }
        // 20 běžných karet, legendary 0,5 % → prakticky vše common
        Assert::true(($counts[Rarity::COMMON] ?? 0) >= 18, 'běžné sloty mají padat hlavně na common');
    }


    public function testPoradiOtevreniPokracujeNapricBalicky(): void
    {
        $this->createFullPool('ignis');
        $this->createFullPool('vitae');
        $player = $this->createPlayer('ignis', moonDust: 500);

        $packs = [
            ...$this->facade->buy($player, $this->getFaction('ignis'), 2),
            ...$this->facade->buy($player, $this->getFaction('vitae'), 3),
        ];
        foreach ($packs as $pack) {
            $this->facade->open($player, $pack);
        }

        Assert::same([1, 2, 3, 4, 5], array_map(fn(PlayerPack $p) => $p->getOpenNumber(), $packs));
        Assert::same(Rarity::RARE, $packs[4]->getGuarantee()?->getCode(), '5. otevřený balíček má garanci bez ohledu na frakci');
    }
}


(new PackFacadeTest)->run();
