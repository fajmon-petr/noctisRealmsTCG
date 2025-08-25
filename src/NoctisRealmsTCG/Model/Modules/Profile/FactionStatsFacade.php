<?php declare(strict_types= 1);

namespace App\Model\Modules\Profile;

use Doctrine\DBAL\Connection;

class FactionStatsFacade
{
    public function __construct(private Connection $db) {}

    public function addContribution(
        int $profileId, int $factionId, int $points,
        array $r = ['C'=>0,'U'=>0,'R'=>0,'E'=>0,'L'=>0],
        ?int $seasonId = null
    ): void {
        $sql = "INSERT INTO player_faction_stats
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
            'p'=>$profileId,'f'=>$factionId,'s'=>$seasonId,'pts'=>$points,
            'c'=>$r['C']??0,'u'=>$r['U']??0,'r'=>$r['R']??0,'e'=>$r['E']??0,'l'=>$r['L']??0,
        ]);
    }
}
