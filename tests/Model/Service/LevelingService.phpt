<?php declare(strict_types=1);

/**
 * Test: App\Model\Service\LevelingService
 */

use App\Model\Entity\Player;
use App\Model\Service\LevelingService;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


function createPlayer(int $level, int $xp): Player
{
    $player = new Player();
    $player->setLevel($level);
    $player->setXp($xp);
    return $player;
}


$service = new LevelingService();


test('thresholdFor – křivka 100 * level^1.5', function () use ($service) {
    Assert::same(100, $service->thresholdFor(1));
    Assert::same(283, $service->thresholdFor(2));
    Assert::same(1118, $service->thresholdFor(5));
});


test('thresholdFor – level pod 1 se počítá jako 1', function () use ($service) {
    Assert::same(100, $service->thresholdFor(0));
    Assert::same(100, $service->thresholdFor(-3));
});


test('addXp – pod hranicí levelu jen přičte XP', function () use ($service) {
    $player = createPlayer(1, 10);
    $service->addXp($player, 50);

    Assert::same(1, $player->getLevel());
    Assert::same(60, $player->getXp());
});


test('addXp – přesně na hranici zvedne level a vynuluje XP', function () use ($service) {
    $player = createPlayer(1, 0);
    $service->addXp($player, 100);

    Assert::same(2, $player->getLevel());
    Assert::same(0, $player->getXp());
});


test('addXp – přebytek XP se přenese do dalšího levelu', function () use ($service) {
    $player = createPlayer(1, 90);
    $service->addXp($player, 30);

    Assert::same(2, $player->getLevel());
    Assert::same(20, $player->getXp());
});


test('addXp – umí přeskočit více levelů naráz', function () use ($service) {
    // L1→L2 = 100, L2→L3 = 283 → 393 XP = level 3 + 10 XP
    $player = createPlayer(1, 0);
    $service->addXp($player, 393);

    Assert::same(3, $player->getLevel());
    Assert::same(10, $player->getXp());
});


test('addXp – záporný zisk se ignoruje', function () use ($service) {
    $player = createPlayer(3, 40);
    $service->addXp($player, -500);

    Assert::same(3, $player->getLevel());
    Assert::same(40, $player->getXp());
});


test('percent – podíl XP do dalšího levelu, zaokrouhlený dolů', function () use ($service) {
    Assert::same(0, $service->percent(createPlayer(1, 0)));
    Assert::same(50, $service->percent(createPlayer(1, 50)));
    Assert::same(99, $service->percent(createPlayer(1, 99)));
    Assert::same(33, $service->percent(createPlayer(2, 94))); // 94 / 283 = 33,2 %
});
