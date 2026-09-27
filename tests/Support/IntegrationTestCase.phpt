<?php declare(strict_types=1);

/**
 * Test: Tests\Support\IntegrationTestCase – ověření infrastruktury integračních testů
 */

use App\Bootstrap;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Tester\Assert;
use Tests\Support\IntegrationTestCase;

require __DIR__ . '/../bootstrap.php';


final class IntegrationTestCaseTest extends IntegrationTestCase
{
    public function testPracujeSTestovaciDatabazi(): void
    {
        Assert::same('noctis_test', $this->em->getConnection()->getDatabase());
    }


    public function testDataPoTestuZmiziRollbackem(): void
    {
        $email = 'rollback-' . bin2hex(random_bytes(6)) . '@example.com';
        $this->createUser($email);
        Assert::notNull($this->em->getRepository(User::class)->findOneBy(['email' => $email]));

        $this->tearDown(); // rollback jako po skončení testu

        $em = (new Bootstrap)->bootTestContainer()->getByType(EntityManagerInterface::class);
        Assert::null($em->getRepository(User::class)->findOneBy(['email' => $email]), 'uživatel po rollbacku v DB zůstal');

        $this->setUp(); // aby tearDown po testu měl s čím pracovat
    }


    public function testPomocneMetodyVytvoriData(): void
    {
        $player = $this->createPlayer('noctis', moonDust: 150);
        $card = $this->createCard('E', 'noctis');

        Assert::same('noctis', $player->getFaction()?->getSlug());
        Assert::same(150, $player->getMoonDust());
        Assert::same('E', $card->getRarity()->getCode());
        Assert::same('Epic', $card->getRarity()->getName());
    }
}


(new IntegrationTestCaseTest)->run();
