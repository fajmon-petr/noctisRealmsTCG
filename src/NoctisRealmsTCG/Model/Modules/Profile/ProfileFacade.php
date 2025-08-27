<?php declare(strict_types=1);

namespace App\Model\Modules\Profile;

use App\Model\Entity\PlayerSeasonStats;
use App\Model\Entity\Profile;
use App\Model\Entity\Season;
use App\Model\Entity\User;
use Doctrine\Migrations\Exception\PlanAlreadyExecuted;
use Doctrine\ORM\EntityManagerInterface;

class ProfileFacade
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getOrCreateForUser(User $user): Profile
    {
        $repo = $this->em->getRepository(Profile::class);
        /** @var Profile|null $p */
        $p = $repo->findOneBy(['user' => $user]);
        if ($p) {
            return $p;
        }

        $p = new Profile();
        $p->setUser($user);
        $this->em->persist($p);
        $this->em->flush();
        return $p;
    }

    public function setFactionBySlug(User $user, string $slug): Profile
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
