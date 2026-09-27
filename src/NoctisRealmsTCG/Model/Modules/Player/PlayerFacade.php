<?php declare(strict_types=1);

namespace App\Model\Modules\Player;

use App\Model\Entity\Player;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PlayerFacade
{
    public EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
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

    public function setFactionBySlug(User $user, string $slug): Player
    {
        $repoF = $this->em->getRepository(\App\Model\Entity\Faction::class);
        /** @var \App\Model\Entity\Faction|null $faction */
        $faction = $repoF->findOneBy(['slug' => $slug]);
        if (!$faction) {
            throw new \InvalidArgumentException('Neplatná frakce.');
        }

        $p = $this->getOrCreateForUser($user);
        $p->setFaction($faction);

        $this->em->flush();

        return $p;
    }
}
