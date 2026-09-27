<?php declare(strict_types=1);

namespace App\Model\Modules\Faction;

use Doctrine\DBAL\Connection;

final class FactionStatsFacade
{
    public function __construct(
        private Connection $db,
    ) {}

    /**
     * Přičte frakci příspěvek (body + počty karet dle rarity) v dané sezóně (nebo all-time při NULL).
     * $r = ['C'=>int,'U'=>int,'R'=>int,'E'=>int,'L'=>int]
     */
    public function addContribution(
        int $factionId,
        int $points,
        array $r = ['C'=>0,'U'=>0,'R'=>0,'E'=>0,'L'=>0],
        ?int $seasonId = null
    ): void {
        $sql = "INSERT INTO faction_season_stats
                  (faction_id, season_id, points_total, cards_common, cards_uncommon, cards_rare, cards_epic, cards_legendary)
                VALUES (:f, :s, :pts, :c, :u, :r, :e, :l)
                ON DUPLICATE KEY UPDATE
                  points_total   = points_total   + VALUES(points_total),
                  cards_common   = cards_common   + VALUES(cards_common),
                  cards_uncommon = cards_uncommon + VALUES(cards_uncommon),
                  cards_rare     = cards_rare     + VALUES(cards_rare),
                  cards_epic     = cards_epic     + VALUES(cards_epic),
                  cards_legendary= cards_legendary+ VALUES(cards_legendary)";
        $this->db->executeStatement($sql, [
            'f'   => $factionId,
            's'   => $seasonId,
            'pts' => $points,
            'c'   => (int)($r['C'] ?? 0),
            'u'   => (int)($r['U'] ?? 0),
            'r'   => (int)($r['R'] ?? 0),
            'e'   => (int)($r['E'] ?? 0),
            'l'   => (int)($r['L'] ?? 0),
        ]);
    }

    /**
     * Vráti stats pro frakci v sezóně ve formátu očekávaném šablonou (klíče 'points', 'common'…)
     * Když řádek neexistuje, vrátí nuly.
     * @return array{points:int, common:int, uncommon:int, rare:int, epic:int, legendary:int}
     */
    public function getSeasonStatsView(int $factionId, ?int $seasonId): array
    {
        $params = ['f' => $factionId];
        if ($seasonId === null) {
            $sql = "SELECT points_total, cards_common, cards_uncommon, cards_rare, cards_epic, cards_legendary
                    FROM faction_season_stats
                    WHERE faction_id = :f AND season_id IS NULL
                    LIMIT 1";
        } else {
            $sql = "SELECT points_total, cards_common, cards_uncommon, cards_rare, cards_epic, cards_legendary
                    FROM faction_season_stats
                    WHERE faction_id = :f AND season_id = :s
                    LIMIT 1";
            $params['s'] = $seasonId;
        }

        $row = $this->db->fetchAssociative($sql, $params) ?: [];

        return [
            'points'    => (int)($row['points_total']   ?? 0),
            'common'    => (int)($row['cards_common']   ?? 0),
            'uncommon'  => (int)($row['cards_uncommon'] ?? 0),
            'rare'      => (int)($row['cards_rare']     ?? 0),
            'epic'      => (int)($row['cards_epic']     ?? 0),
            'legendary' => (int)($row['cards_legendary']?? 0),
        ];
    }

    /**
     * Leaderboard frakcí v dané sezóně (nebo all-time při NULL).
     * Vrací: id, name, slug, color, points_total a raritní součty.
     * @return array<int, array<string, mixed>>
     */
    public function getLeaderboard(?int $seasonId): array
    {
        // Pozn.: názvy tabulek/sloupců frakce si uprav dle své schémy (factions: id, name, slug, color)
        $params = [];
        if ($seasonId === null) {
            $sql = "SELECT f.id, f.name, f.slug, f.color,
                           s.points_total, s.cards_common, s.cards_uncommon, s.cards_rare, s.cards_epic, s.cards_legendary
                    FROM factions f
                    JOIN faction_season_stats s ON s.faction_id = f.id AND s.season_id IS NULL
                    ORDER BY s.points_total DESC, f.id ASC";
        } else {
            $sql = "SELECT f.id, f.name, f.slug, f.color,
                           s.points_total, s.cards_common, s.cards_uncommon, s.cards_rare, s.cards_epic, s.cards_legendary
                    FROM factions f
                    JOIN faction_season_stats s ON s.faction_id = f.id AND s.season_id = :sid
                    ORDER BY s.points_total DESC, f.id ASC";
            $params['sid'] = $seasonId;
        }

        return $this->db->fetchAllAssociative($sql, $params);
    }

    /**
     * (Volitelné) Nastaví finální umístění frakce v sezóně (po uzavření sezóny).
     */
    public function setFinalRank(int $factionId, int $seasonId, int $rank): void
    {
        $sql = "INSERT INTO faction_season_stats (faction_id, season_id, final_rank)
                VALUES (:f,:s,:r)
                ON DUPLICATE KEY UPDATE final_rank = VALUES(final_rank)";
        $this->db->executeStatement($sql, ['f'=>$factionId,'s'=>$seasonId,'r'=>$rank]);
    }
}
