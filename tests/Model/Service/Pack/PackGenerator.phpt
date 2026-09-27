<?php declare(strict_types=1);

/**
 * Test: App\Model\Service\Pack\PackGenerator
 */

use App\Model\Entity\Rarity;
use App\Model\Service\Pack\PackGenerator;
use App\Model\Service\Pack\PackRules;
use App\Model\Service\Pack\PackSlot;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


// základní šance varianty B (tabulka `rarity`)
const BASE_CHANCES = [
    Rarity::COMMON => 68.0,
    Rarity::UNCOMMON => 25.0,
    Rarity::RARE => 4.5,
    Rarity::EPIC => 2.0,
    Rarity::LEGENDARY => 0.5,
];


function createGenerator(int $seed = 42): PackGenerator
{
    return new PackGenerator(new Randomizer(new Mt19937($seed)));
}


/**
 * Rozložení výsledků v procentech.
 * @param list<string> $results
 * @return array<string, float>
 */
function distribution(array $results): array
{
    $counts = array_count_values($results);
    return array_map(fn(int $c) => $c / count($results) * 100, $counts);
}


test('balíček má vždy 5 karet', function () {
    $generator = createGenerator();
    for ($n = 1; $n <= 40; $n++) {
        Assert::count(PackRules::CARDS_PER_PACK, $generator->generate($n, BASE_CHANCES), "balíček č. $n");
    }
});


test('balíček bez garance nemá garantovaný slot', function () {
    $generator = createGenerator();
    foreach ([1, 2, 3, 4, 6, 7, 11, 19] as $n) {
        $guaranteed = array_filter($generator->generate($n, BASE_CHANCES), fn(PackSlot $s) => $s->guaranteed);
        Assert::count(0, $guaranteed, "balíček č. $n");
    }
});


test('balíček s garancí má přesně 1 garantovaný slot, a to poslední', function () {
    $generator = createGenerator();
    foreach ([5, 10, 15, 20, 25, 30, 40] as $n) {
        $slots = $generator->generate($n, BASE_CHANCES);
        $guaranteed = array_filter($slots, fn(PackSlot $s) => $s->guaranteed);

        Assert::count(1, $guaranteed, "balíček č. $n");
        Assert::true($slots[PackRules::CARDS_PER_PACK - 1]->guaranteed, "balíček č. $n – garance je poslední slot");
    }
});


test('garantovaná karta splňuje minimum garance', function () {
    $generator = createGenerator();
    $allowed = [
        5 => [Rarity::RARE, Rarity::EPIC],
        10 => [Rarity::EPIC],
        20 => [Rarity::LEGENDARY],
    ];
    foreach ($allowed as $n => $rarities) {
        for ($i = 0; $i < 500; $i++) {
            $slots = $generator->generate($n, BASE_CHANCES);
            Assert::contains($slots[PackRules::CARDS_PER_PACK - 1]->rarity, $rarities, "balíček č. $n");
        }
    }
});


test('stejný seed = stejný balíček', function () {
    $a = createGenerator(123)->generate(5, BASE_CHANCES);
    $b = createGenerator(123)->generate(5, BASE_CHANCES);
    Assert::equal($a, $b);
});


test('základní šance – rozložení odpovídá tabulce rarit (±0,5 p. b.)', function () {
    $generator = createGenerator();
    $results = [];
    for ($i = 0; $i < 100_000; $i++) {
        $results[] = $generator->pick(BASE_CHANCES);
    }
    $actual = distribution($results);

    foreach (BASE_CHANCES as $rarity => $expected) {
        Assert::true(
            abs(($actual[$rarity] ?? 0) - $expected) <= 0.5,
            sprintf('%s: čekáno %.2f %%, padlo %.2f %%', $rarity, $expected, $actual[$rarity] ?? 0),
        );
    }
});


test('garance rare+ – rozložení podle PackRules (±1 p. b.)', function () {
    $generator = createGenerator();
    $results = [];
    for ($i = 0; $i < 20_000; $i++) {
        $results[] = $generator->pick(PackRules::GUARANTEE_CHANCES[Rarity::RARE]);
    }
    $actual = distribution($results);

    foreach (PackRules::GUARANTEE_CHANCES[Rarity::RARE] as $rarity => $expected) {
        Assert::true(
            abs(($actual[$rarity] ?? 0) - $expected) <= 1,
            sprintf('%s: čekáno %d %%, padlo %.2f %%', $rarity, $expected, $actual[$rarity] ?? 0),
        );
    }
});


test('šance nemusí dávat 100 – berou se poměrově', function () {
    $generator = createGenerator();
    $results = [];
    for ($i = 0; $i < 20_000; $i++) {
        $results[] = $generator->pick([Rarity::COMMON => 3, Rarity::RARE => 1]); // 75 : 25
    }
    Assert::true(abs(distribution($results)[Rarity::COMMON] - 75) <= 1);
});


test('rarita s nulovou šancí nikdy nepadne', function () {
    $generator = createGenerator();
    for ($i = 0; $i < 5_000; $i++) {
        Assert::notSame(Rarity::LEGENDARY, $generator->pick([Rarity::COMMON => 99, Rarity::LEGENDARY => 0]));
    }
});


test('pickRandom – vybírá rovnoměrně ze všech prvků', function () {
    $generator = createGenerator();
    $results = [];
    for ($i = 0; $i < 30_000; $i++) {
        $results[] = $generator->pickRandom(['a', 'b', 'c']);
    }
    foreach (distribution($results) as $item => $percent) {
        Assert::true(abs($percent - 100 / 3) <= 1, "$item: padlo $percent %");
    }
    Assert::same('x', $generator->pickRandom(['x']));
    Assert::exception(fn() => $generator->pickRandom([]), InvalidArgumentException::class);
});


test('neplatné šance jsou chyba', function () {
    $generator = createGenerator();
    Assert::exception(fn() => $generator->pick([]), InvalidArgumentException::class);
    Assert::exception(fn() => $generator->pick([Rarity::COMMON => 0]), InvalidArgumentException::class);
    Assert::exception(fn() => $generator->pick([Rarity::COMMON => -5, Rarity::RARE => 10]), InvalidArgumentException::class);
});
