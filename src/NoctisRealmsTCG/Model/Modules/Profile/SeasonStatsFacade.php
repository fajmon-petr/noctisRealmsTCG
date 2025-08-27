<?php declare(strict_types=1);

namespace App\Model\Modules\Profile;

use App\Model\Entity\PlayerSeasonStats;
use App\Model\Entity\Profile;
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

  public function addContribution(
    int $profileId,
    int $factionId,
    int $points,
    array $r = ['C' => 0, 'U' => 0, 'R' => 0, 'E' => 0, 'L' => 0],
    ?int $seasonId = null
  ): void {
    $sql = "INSERT INTO player_season_stats
          (profile_id,faction_id,season_id,points_total,cards_common,cards_uncommon,cards_rare,cards_epic,cards_legendary)
        VALUES (:p,:f,:s,:pts,:c,:u,:r,:e,:l)
        ON DUPLICATE KEY UPDATE
          points_total=points_total+VALUES(points_total),
          cards_common=cards_common+VALUES(cards_common),
          cards_uncommon=cards_uncommon+VALUES(cards_uncommon),
          cards_rare=cards_rare+VALUES(cards_rare),
          cards_epic=cards_epic+VALUES(cards_epic),
          cards_legendary=cards_legendary+VALUES(cards_legendary)";
    $this->db->executeStatement($sql, [
      'p' => $profileId,
      'f' => $factionId,
      's' => $seasonId,
      'pts' => $points,
      'c' => $r['C'] ?? 0,
      'u' => $r['U'] ?? 0,
      'r' => $r['R'] ?? 0,
      'e' => $r['E'] ?? 0,
      'l' => $r['L'] ?? 0,
    ]);
  }

  public function allSeasonsWithMyStats(Profile $profile): array
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
        'pss.season = s AND pss.profile = :profile'
      )
      ->leftJoin('pss.faction', 'f')
      ->setParameter('profile', $profile)
      ->orderBy('s.startAt', 'DESC');

    return $qb->getQuery()->getArrayResult();
  }

  public function getLiveRank(int $profileId, int $seasonId)
  {
    $repo = $this->em->getRepository(PlayerSeasonStats::class);

    // 1) moje body v dané sezóně
    $pts = $repo->createQueryBuilder('p')
      ->select('p.pointsTotal')
      ->where('IDENTITY(p.season) = :sid')
      ->andWhere('IDENTITY(p.profile) = :pid')
      ->setParameter('sid', $seasonId)
      ->setParameter('pid', $profileId)
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
