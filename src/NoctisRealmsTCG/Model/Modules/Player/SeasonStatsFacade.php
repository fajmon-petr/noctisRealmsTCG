<?php declare(strict_types=1);

namespace App\Model\Modules\Player;

use App\Model\Entity\Rarity;
use App\Model\Entity\PlayerSeasonStats;
use App\Model\Entity\Player;
use App\Model\Entity\Season;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;

class SeasonStatsFacade
{
  private Connection $connection;

  private EntityManagerInterface $em;

  public function __construct(Connection $db, EntityManagerInterface $em)
  {
    $this->connection = $db;
    $this->em = $em;
  }

  /**
   * Přičte hráči příspěvek do sezónní statistiky (body + počty karet podle rarity).
   * Atomicky (INSERT … ON DUPLICATE KEY UPDATE) – souběžné příspěvky se neztratí.
   *
   * @param array<string, int> $cardsByRarity kód rarity (C/U/R/E/L) => počet karet
   */
  public function addContribution(int $playerId, int $factionId, int $seasonId, int $points, array $cardsByRarity = []): void
  {
    $sql = "INSERT INTO player_season_stat
          (player_id, faction_id, season_id, points_total, common, uncommon, rare, epic, legendary)
        VALUES (:p, :f, :s, :pts, :c, :u, :r, :e, :l)
        ON DUPLICATE KEY UPDATE
          points_total = points_total + VALUES(points_total),
          common = common + VALUES(common),
          uncommon = uncommon + VALUES(uncommon),
          rare = rare + VALUES(rare),
          epic = epic + VALUES(epic),
          legendary = legendary + VALUES(legendary)";
    $this->connection->executeStatement($sql, [
      'p' => $playerId,
      'f' => $factionId,
      's' => $seasonId,
      'pts' => $points,
      'c' => $cardsByRarity[Rarity::COMMON] ?? 0,
      'u' => $cardsByRarity[Rarity::UNCOMMON] ?? 0,
      'r' => $cardsByRarity[Rarity::RARE] ?? 0,
      'e' => $cardsByRarity[Rarity::EPIC] ?? 0,
      'l' => $cardsByRarity[Rarity::LEGENDARY] ?? 0,
    ]);
  }

  public function allSeasonsWithMyStats(Player $player): array
  {
    $qb = $this->em->createQueryBuilder();

    $qb->select([
      's.id            AS seasonId',
      's.name          AS seasonName',
      's.startAt       AS startAt',
      's.endAt         AS endAt',
      // stats (coalesce → 0 když neexistuje řádek)
      'pss.finalRank AS finalRank',
      'COALESCE(pss.pointsTotal, 0) AS points',
      'COALESCE(pss.common,      0) AS common',
      'COALESCE(pss.uncommon,    0) AS uncommon',
      'COALESCE(pss.rare,        0) AS rare',
      'COALESCE(pss.epic,        0) AS epic',
      'COALESCE(pss.legendary,   0) AS legendary',
      // frakce z dané sezóny (pokud ji v té sezóně měl)
      'f.id     AS factionId',
      'f.name   AS factionName',
      'f.slug   AS factionSlug',
      'f.color  AS factionColor',
    ])
      ->from(Season::class, 's')
      // klíčová část: left join na stats jen pro tento profil
      ->leftJoin(
        PlayerSeasonStats::class,
        'pss',
        'WITH',
        'pss.season = s AND pss.player = :player'
      )
      ->leftJoin('pss.faction', 'f')
      ->setParameter('player', $player)
      ->orderBy('s.startAt', 'DESC');

    return $qb->getQuery()->getArrayResult();
  }

  public function getLiveRank(int $playerId, int $seasonId)
  {
    $repo = $this->em->getRepository(PlayerSeasonStats::class);

    // 1) moje body v dané sezóně
    $pts = $repo->createQueryBuilder('p')
      ->select('p.pointsTotal')
      ->where('IDENTITY(p.season) = :sid')
      ->andWhere('IDENTITY(p.player) = :pid')
      ->setParameter('sid', $seasonId)
      ->setParameter('pid', $playerId)
      ->getQuery()
      ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

    if ($pts === null) {
      // hráč v sezóně nemá záznam => žádný rank
      return null;
    }

    // 2) kolik hráčů má víc bodů → dense rank = 1 + count(větších)
    $cnt = $repo->createQueryBuilder('x')
      ->select('COUNT(x.id)')
      ->where('IDENTITY(x.season) = :sid')
      ->andWhere('x.pointsTotal > :pts')
      ->setParameter('sid', $seasonId)
      ->setParameter('pts', (int) $pts)
      ->getQuery()
      ->getSingleScalarResult();

    return 1 + (int) $cnt;
  }

}
