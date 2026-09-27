<?php declare(strict_types=1);

/**
 * Test: App\Model\Modules\Player\PlayerFacade (integrační, DB noctis_test)
 */

use App\Model\Entity\Player;
use App\Model\Modules\Player\PlayerFacade;
use Tester\Assert;
use Tests\Support\IntegrationTestCase;

require __DIR__ . '/../../../bootstrap.php';


final class PlayerFacadeTest extends IntegrationTestCase
{
    private PlayerFacade $facade;


    protected function setUp(): void
    {
        parent::setUp();
        $this->facade = $this->getService(PlayerFacade::class);
    }


    public function testGetForUserIdVytvoriProfilJenJednou(): void
    {
        $user = $this->createUser();

        $first = $this->facade->getForUserId((int) $user->getId());
        $second = $this->facade->getForUserId((int) $user->getId());

        Assert::same($first->getId(), $second->getId());
        Assert::count(1, $this->em->getRepository(Player::class)->findBy(['user' => $user]));
    }


    public function testGetForUserIdNeexistujiciUzivatel(): void
    {
        Assert::exception(fn() => $this->facade->getForUserId(999_999_999), InvalidArgumentException::class);
    }


    public function testFindByUserIdBezProfilu(): void
    {
        $user = $this->createUser();
        Assert::null($this->facade->findByUserId((int) $user->getId()));
    }


    public function testPrvniVyberFrakceDaBonus(): void
    {
        $player = $this->createPlayer(moonDust: 30);

        $first = $this->facade->saveProfile($player, 'Nováček', 'vitae');

        Assert::true($first);
        Assert::same('Nováček', $player->getNickname());
        Assert::same('vitae', $player->getFaction()?->getSlug());
        Assert::same(30 + PlayerFacade::FIRST_FACTION_BONUS, $player->getMoonDust());
    }


    public function testZmenaProfiluBonusNedava(): void
    {
        $player = $this->createPlayer('ignis', moonDust: 30);

        $first = $this->facade->saveProfile($player, 'Veterán', 'noctis');

        Assert::false($first);
        Assert::same('noctis', $player->getFaction()?->getSlug());
        Assert::same(30, $player->getMoonDust());
    }


    public function testUlozeniSePropiseDoDb(): void
    {
        $player = $this->createPlayer();
        $this->facade->saveProfile($player, 'Uložený', 'ignis');

        $this->em->clear();
        $reloaded = $this->em->find(Player::class, $player->getId());

        Assert::same('Uložený', $reloaded?->getNickname());
        Assert::same('ignis', $reloaded?->getFaction()?->getSlug());
        Assert::same(PlayerFacade::FIRST_FACTION_BONUS, $reloaded?->getMoonDust());
    }


    public function testNeplatnaFrakceNicNezmeni(): void
    {
        $player = $this->createPlayer(moonDust: 10);

        Assert::exception(
            fn() => $this->facade->saveProfile($player, 'Nikdo', 'neexistuje'),
            InvalidArgumentException::class,
        );
        Assert::null($player->getFaction());
        Assert::same(10, $player->getMoonDust());
    }
}


(new PlayerFacadeTest)->run();
