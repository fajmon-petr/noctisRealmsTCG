<?php declare(strict_types=1);

namespace App\Model\Modules\Player;

use App\Model\Entity\Faction;
use App\Model\Entity\Player;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PlayerFacade
{
    /** Jednorázový bonus Moon Dustu za první výběr frakce */
    public const FIRST_FACTION_BONUS = 200;

    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function findByUserId(int $userId): ?Player
    {
        return $this->em->getRepository(Player::class)->findOneBy(['user' => $userId]);
    }

    /** Profil přihlášeného uživatele – vytvoří ho, pokud ještě neexistuje */
    public function getForUserId(int $userId): Player
    {
        $user = $this->em->find(User::class, $userId);
        if (!$user) {
            throw new \InvalidArgumentException("Uživatel $userId neexistuje.");
        }

        return $this->getOrCreateForUser($user);
    }

    public function getOrCreateForUser(User $user): Player
    {
        $repo = $this->em->getRepository(Player::class);
        /** @var Player|null $p */
        $p = $repo->findOneBy(['user' => $user]);
        if ($p) {
            return $p;
        }

        $p = new Player();
        $p->setUser($user);
        $this->em->persist($p);
        $this->em->flush();
        return $p;
    }

    /**
     * Uloží jméno a frakci hráče. Při první volbě frakce připíše bonus Moon Dustu.
     *
     * @return bool true, pokud šlo o první volbu frakce (a byl připsán bonus)
     */
    public function saveProfile(Player $player, string $nickname, string $factionSlug): bool
    {
        $faction = $this->em->getRepository(Faction::class)->findOneBy(['slug' => $factionSlug]);
        if (!$faction) {
            throw new \InvalidArgumentException('Neplatná frakce.');
        }

        $firstFactionChoice = $player->getFaction() === null;

        $this->em->wrapInTransaction(function () use ($player, $nickname, $faction, $firstFactionChoice): void {
            $player->setNickname($nickname);
            $player->setFaction($faction);

            if ($firstFactionChoice) {
                $player->addMoonDust(self::FIRST_FACTION_BONUS);
            }

            $this->em->flush();
        });

        return $firstFactionChoice;
    }
}
