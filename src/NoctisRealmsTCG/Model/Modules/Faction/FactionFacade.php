<?php
declare(strict_types=1);

namespace App\Model\Modules\Faction;

use App\Model\Entity\Faction;
use App\Model\Entity\FactionSeasonStats;
use App\Model\Entity\Season;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

class FactionFacade
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getFactionBySlugOrDefault(?string $slug): ?Faction
    {
        $repo = $this->em->getRepository(Faction::class);

        if ($slug) {
            $bySlug = $repo->findOneBy(['slug' => $slug]);
            if ($bySlug) {
                return $bySlug;
            }
        }

        // deterministický default
        foreach (['ignis','Ignis','IGNS'] as $try) {
            $byCode = $repo->findOneBy(['name' => $try]);
            if ($byCode) {
                return $byCode;
            }
        }

        return $repo->findOneBy([]); // první v DB jako nouzovka
    }

    public function getCurrentSeason(): ?Season
    {
        $repo = $this->em->getRepository(Season::class);
        $now  = new DateTimeImmutable();

        return $repo->createQueryBuilder('s')
            ->where('s.startAt <= :now')
            ->andWhere('(s.endAt IS NULL OR s.endAt >= :now)')
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array{pointsTotal:int, cardsByRarity:array{C:int,U:int,R:int,E:int,L:int}}
     */
    public function getSeasonStats(int $factionId, ?int $seasonId): array
    {
        $repo = $this->em->getRepository(FactionSeasonStats::class);

        $criteria = ['faction' => $factionId];
        $seasonId === null
            ? $criteria['season'] = null
            : $criteria['season'] = $seasonId;

        /** @var FactionSeasonStats|null $row */
        $row = $repo->findOneBy($criteria);

        if (!$row) {
            return [
                'pointsTotal'   => 0,
                'cardsByRarity' => ['C'=>0,'U'=>0,'R'=>0,'E'=>0,'L'=>0],
            ];
        }

        return [
            'pointsTotal'   => (int)$row->getPointsTotal(),
            'cardsByRarity' => [
                'C' => (int)$row->getCardsCommon(),
                'U' => (int)$row->getCardsUncommon(),
                'R' => (int)$row->getCardsRare(),
                'E' => (int)$row->getCardsEpic(),
                'L' => (int)$row->getCardsLegendary(),
            ],
        ];
    }
}
