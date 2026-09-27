<?php declare(strict_types=1);

namespace App\Model\Modules\Faction;

use App\Model\Entity\Rarity;
use Doctrine\DBAL\Connection;

/**
 * Sezónní statistiky frakcí (tabulka `faction_season_stat`).
 * Zápisy jsou atomické (INSERT … ON DUPLICATE KEY UPDATE), aby se při souběžných příspěvcích
 * více hráčů body neztratily.
 */
final class FactionStatsFacade
{
    public function __construct(
        private Connection $db,
    ) {}

    /**
     * Přičte frakci příspěvek (body + počty karet podle rarity) v dané sezóně.
     *
     * @param array<string, int> $cardsByRarity kód rarity (C/U/R/E/L) => počet karet
     */
    public function addContribution(int $factionId, int $seasonId, int $points, array $cardsByRarity = []): void
    {
        $sql = "INSERT INTO faction_season_stat
                  (faction_id, season_id, points_total, common, uncommon, rare, epic, legendary)
                VALUES (:f, :s, :pts, :c, :u, :r, :e, :l)
                ON DUPLICATE KEY UPDATE
                  points_total = points_total + VALUES(points_total),
                  common       = common       + VALUES(common),
                  uncommon     = uncommon     + VALUES(uncommon),
                  rare         = rare         + VALUES(rare),
                  epic         = epic         + VALUES(epic),
                  legendary    = legendary    + VALUES(legendary)";
        $this->db->executeStatement($sql, [
            'f'   => $factionId,
            's'   => $seasonId,
            'pts' => $points,
            'c'   => $cardsByRarity[Rarity::COMMON] ?? 0,
            'u'   => $cardsByRarity[Rarity::UNCOMMON] ?? 0,
            'r'   => $cardsByRarity[Rarity::RARE] ?? 0,
            'e'   => $cardsByRarity[Rarity::EPIC] ?? 0,
            'l'   => $cardsByRarity[Rarity::LEGENDARY] ?? 0,
        ]);
    }

    /**
     * Žebříček frakcí v sezóně: id, name, slug, color, points_total a počty karet podle rarity.
     *
     * @return list<array<string, mixed>>
     */
    public function getLeaderboard(int $seasonId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT f.id, f.name, f.slug, f.color,
                    s.points_total, s.common, s.uncommon, s.rare, s.epic, s.legendary
             FROM faction f
             JOIN faction_season_stat s ON s.faction_id = f.id AND s.season_id = :sid
             ORDER BY s.points_total DESC, f.id ASC",
            ['sid' => $seasonId],
        );
    }

    /** Finální umístění frakce v sezóně (po uzavření sezóny) */
    public function setFinalRank(int $factionId, int $seasonId, int $rank): void
    {
        $this->db->executeStatement(
            "INSERT INTO faction_season_stat (faction_id, season_id, final_rank)
             VALUES (:f, :s, :r)
             ON DUPLICATE KEY UPDATE final_rank = VALUES(final_rank)",
            ['f' => $factionId, 's' => $seasonId, 'r' => $rank],
        );
    }
}
