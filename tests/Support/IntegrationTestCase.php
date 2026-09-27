<?php declare(strict_types=1);

namespace Tests\Support;

use App\Bootstrap;
use App\Model\Entity\Card;
use App\Model\Entity\Faction;
use App\Model\Entity\Player;
use App\Model\Entity\Rarity;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nette\DI\Container;
use Tester\Environment;
use Tester\TestCase;

/**
 * Základ integračních testů nad DB `noctis_test`.
 *
 * - Každý test dostane čerstvý kontejner (a EntityManager) a běží v transakci,
 *   která se po testu vrátí (rollback) – v DB po testech nic nezůstane.
 * - Integrační testy běží jeden po druhém (zámek), i když Tester pouští soubory paralelně.
 * - Ochrana: pokud by kontejner nemířil na DB končící `_test`, test se nespustí.
 *
 * `noctis_test` musí mít aktuální schéma (`php vendor/bin/phinx migrate -e testing`)
 * a základní data (`php vendor/bin/phinx seed:run -e testing`). Karty, uživatele a hráče
 * si testy vytváří samy přes pomocné metody níže.
 */
abstract class IntegrationTestCase extends TestCase
{
    private static bool $locked = false;

    protected Container $container;

    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        if (!self::$locked) {
            Environment::lock('noctis_test', dirname(__DIR__, 2) . '/temp');
            self::$locked = true;
        }

        $this->container = (new Bootstrap)->bootTestContainer();
        $this->em = $this->container->getByType(EntityManagerInterface::class);

        $database = $this->em->getConnection()->getDatabase();
        if ($database === null || !str_ends_with($database, '_test')) {
            throw new \RuntimeException("Integrační testy smí běžet jen proti testovací DB (*_test), ne proti '$database'.");
        }

        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->em->getConnection();
        while ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
        $this->em->clear();
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    protected function getService(string $type): object
    {
        return $this->container->getByType($type);
    }

    // --- Pomocné metody pro testovací data ---

    protected function getFaction(string $slug): Faction
    {
        return $this->em->getRepository(Faction::class)->findOneBy(['slug' => $slug])
            ?? throw new \LogicException("Frakce '$slug' v noctis_test chybí – spusť seedy.");
    }

    protected function getRarity(string $code): Rarity
    {
        return $this->em->find(Rarity::class, $code)
            ?? throw new \LogicException("Rarita '$code' v noctis_test chybí – spusť migrace.");
    }

    protected function createUser(?string $email = null): User
    {
        $user = new User();
        $user->setEmail($email ?? 'test-' . bin2hex(random_bytes(6)) . '@example.com');
        $user->setPassword('neplatny-hash');
        $user->setRole($this->em->getRepository(Role::class)->findOneBy(['slug' => 'player'])
            ?? throw new \LogicException('Role player v noctis_test chybí – spusť seedy.'));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    protected function createPlayer(?string $factionSlug = null, int $moonDust = 0): Player
    {
        $player = new Player();
        $player->setUser($this->createUser());
        $player->setNickname('Tester');
        $player->setFaction($factionSlug !== null ? $this->getFaction($factionSlug) : null);
        $player->setMoonDust($moonDust);
        $this->em->persist($player);
        $this->em->flush();
        return $player;
    }

    protected function createCard(string $rarityCode, ?string $factionSlug, ?string $name = null): Card
    {
        $card = new Card($name ?? "Karta $rarityCode", $this->getRarity($rarityCode), '/assets/cards/test.png');
        if ($factionSlug !== null) {
            $card->setFaction($this->getFaction($factionSlug));
        }
        $this->em->persist($card);
        $this->em->flush();
        return $card;
    }
}
