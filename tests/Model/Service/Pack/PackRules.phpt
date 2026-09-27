<?php declare(strict_types=1);

/**
 * Test: App\Model\Service\Pack\PackRules
 */

use App\Model\Entity\Rarity;
use App\Model\Service\Pack\PackRules;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


test('guaranteeFor – rozvrh 5. rare+, 10. epic+, 20. legendary a dál dokola', function () {
    $expected = [
        5 => Rarity::RARE, 10 => Rarity::EPIC, 15 => Rarity::RARE, 20 => Rarity::LEGENDARY,
        25 => Rarity::RARE, 30 => Rarity::EPIC, 35 => Rarity::RARE, 40 => Rarity::LEGENDARY,
        45 => Rarity::RARE, 50 => Rarity::EPIC, 100 => Rarity::LEGENDARY,
    ];
    foreach ($expected as $n => $rarity) {
        Assert::same($rarity, PackRules::guaranteeFor($n), "balíček č. $n");
    }
});


test('guaranteeFor – ostatní balíčky garanci nemají', function () {
    foreach ([1, 2, 3, 4, 6, 9, 11, 19, 21, 39, 41] as $n) {
        Assert::null(PackRules::guaranteeFor($n), "balíček č. $n");
    }
});


test('guaranteeFor – na 40 balíčků přesně 4× rare+, 2× epic+, 2× legendary', function () {
    $counts = [];
    for ($n = 1; $n <= 40; $n++) {
        $g = PackRules::guaranteeFor($n);
        if ($g !== null) {
            $counts[$g] = ($counts[$g] ?? 0) + 1;
        }
    }
    Assert::same([Rarity::RARE => 4, Rarity::EPIC => 2, Rarity::LEGENDARY => 2], $counts);
});


test('guaranteeFor – pořadí pod 1 je chyba', function () {
    Assert::exception(fn() => PackRules::guaranteeFor(0), InvalidArgumentException::class);
});


test('packsUntilGuarantee – kolik balíčků zbývá do garance', function () {
    // nic neotevřeno
    Assert::same(5, PackRules::packsUntilGuarantee(0, Rarity::RARE));
    Assert::same(10, PackRules::packsUntilGuarantee(0, Rarity::EPIC));
    Assert::same(20, PackRules::packsUntilGuarantee(0, Rarity::LEGENDARY));

    // po 3 otevřených
    Assert::same(2, PackRules::packsUntilGuarantee(3, Rarity::RARE));
    Assert::same(7, PackRules::packsUntilGuarantee(3, Rarity::EPIC));
    Assert::same(17, PackRules::packsUntilGuarantee(3, Rarity::LEGENDARY));

    // po 5 otevřených: další rare+ až 15. (10. je epic+)
    Assert::same(10, PackRules::packsUntilGuarantee(5, Rarity::RARE));
    Assert::same(5, PackRules::packsUntilGuarantee(5, Rarity::EPIC));

    // po 20 otevřených: pokračuje se 25. / 30. / 40.
    Assert::same(5, PackRules::packsUntilGuarantee(20, Rarity::RARE));
    Assert::same(10, PackRules::packsUntilGuarantee(20, Rarity::EPIC));
    Assert::same(20, PackRules::packsUntilGuarantee(20, Rarity::LEGENDARY));
});


test('packsUntilGuarantee – neznámá garance je chyba', function () {
    Assert::exception(fn() => PackRules::packsUntilGuarantee(0, Rarity::COMMON), InvalidArgumentException::class);
});


test('šance garantovaných slotů dávají 100 % a jen povolené rarity', function () {
    $order = [Rarity::COMMON, Rarity::UNCOMMON, Rarity::RARE, Rarity::EPIC, Rarity::LEGENDARY];
    foreach (PackRules::GUARANTEE_CHANCES as $minimum => $chances) {
        Assert::same(100, array_sum($chances), "garance $minimum");
        foreach (array_keys($chances) as $rarity) {
            Assert::true(
                array_search($rarity, $order, true) >= array_search($minimum, $order, true),
                "garance $minimum nesmí obsahovat $rarity",
            );
        }
    }
});
