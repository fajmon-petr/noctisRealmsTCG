<?php
declare(strict_types=1);

namespace App\Model\Modules\Faction;

use App\Model\Entity\Achievement;
use App\Model\Entity\Faction;
use App\Model\Entity\FactionSeasonStats;
use App\Model\Entity\Rarity;
use Doctrine\ORM\EntityManagerInterface;

class FactionFacade
{
    /** Frakce zobrazená, když slug chybí nebo neexistuje */
    public const DEFAULT_SLUG = 'ignis';

    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getBySlug(string $slug): ?Faction
    {
        return $this->em->getRepository(Faction::class)->findOneBy(['slug' => $slug]);
    }

    public function getFactionBySlugOrDefault(?string $slug): ?Faction
    {
        $repo = $this->em->getRepository(Faction::class);

        return ($slug ? $repo->findOneBy(['slug' => $slug]) : null)
            ?? $repo->findOneBy(['slug' => self::DEFAULT_SLUG]);
    }

    /**
     * Body a počty darovaných karet frakce v sezóně (bez sezóny / bez záznamu = nuly).
     *
     * @return array{pointsTotal: int, cardsByRarity: array<string, int>} cardsByRarity: kód rarity => počet
     */
    public function getSeasonStats(int $factionId, ?int $seasonId): array
    {
        /** @var FactionSeasonStats|null $row */
        $row = $seasonId === null ? null : $this->em->getRepository(FactionSeasonStats::class)
            ->findOneBy(['faction' => $factionId, 'season' => $seasonId]);

        return [
            'pointsTotal'   => $row?->getPointsTotal() ?? 0,
            'cardsByRarity' => [
                Rarity::COMMON    => $row?->getCommon() ?? 0,
                Rarity::UNCOMMON  => $row?->getUncommon() ?? 0,
                Rarity::RARE      => $row?->getRare() ?? 0,
                Rarity::EPIC      => $row?->getEpic() ?? 0,
                Rarity::LEGENDARY => $row?->getLegendary() ?? 0,
            ],
        ];
    }

    /** @return Faction[] frakce bez neutrální */
    public function getMainFactions(): array
    {
        return $this->em->getRepository(Faction::class)->createQueryBuilder('f')
            ->where('f.slug != :neutral')
            ->setParameter('neutral', Faction::NEUTRAL_SLUG)
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
