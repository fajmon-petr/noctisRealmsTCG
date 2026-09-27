<?php
declare(strict_types=1);

namespace App\Model\Modules\Faction;

use App\Model\Entity\Achievement;
use App\Model\Entity\Faction;
use App\Model\Entity\FactionSeasonStats;
use Doctrine\ORM\EntityManagerInterface;

class FactionFacade
{
    /** Frakce zobrazená, když slug chybí nebo neexistuje */
    public const DEFAULT_SLUG = 'ignis';

    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getFactionBySlugOrDefault(?string $slug): ?Faction
    {
        $repo = $this->em->getRepository(Faction::class);

        return ($slug ? $repo->findOneBy(['slug' => $slug]) : null)
            ?? $repo->findOneBy(['slug' => self::DEFAULT_SLUG]);
    }

    /**
     * @return array{pointsTotal:int, cardsByRarity:array{C:int,U:int,R:int,E:int,L:int}}
     */
    public function getSeasonStats(int $factionId, ?int $seasonId): array
    {
        /** @var FactionSeasonStats|null $row */
        $row = $this->em->getRepository(FactionSeasonStats::class)
            ->findOneBy(['faction' => $factionId, 'season' => $seasonId]);

        if (!$row) {
            return [
                'pointsTotal'   => 0,
                'cardsByRarity' => ['C' => 0, 'U' => 0, 'R' => 0, 'E' => 0, 'L' => 0],
            ];
        }

        return [
            'pointsTotal'   => $row->getPointsTotal(),
            'cardsByRarity' => [
                'C' => $row->getCommon(),
                'U' => $row->getUncommon(),
                'R' => $row->getRare(),
                'E' => $row->getEpic(),
                'L' => $row->getLegendary(),
            ],
        ];
    }

    /** @return Faction[] frakce bez neutrální */
    public function getMainFactions(): array
    {
        return $this->em->getRepository(Faction::class)->createQueryBuilder('f')
            ->where('f.slug != :neutral')
            ->setParameter('neutral', 'neutral')
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Achievement[] */
    public function getFactionAchievements(): array
    {
        return $this->em->getRepository(Achievement::class)->createQueryBuilder('a')
            ->where('a.type = :type')
            ->setParameter('type', 'faction')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
