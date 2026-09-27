<?php declare(strict_types=1);
namespace App\Model\Entity;

use App\Utils\MagicAccessors;
use Doctrine\ORM\Mapping as ORM;
use App\Model\Entity\Faction;

#[ORM\Entity]
#[ORM\Table(name: "player_season_stat")]
#[ORM\UniqueConstraint(name: "uniq_player_faction_season", columns: ["player_id", "faction_id", "season_id"])]
#[ORM\Index(name: "idx_leaderboard", columns: ["faction_id", "points_total"])]
#[ORM\Index(name: "idx_pfs_season_points", columns: ["season_id", "points_total"])]
class PlayerSeasonStats
{
    use MagicAccessors;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: "player_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: "faction_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Faction $faction;

    #[ORM\ManyToOne(targetEntity: Season::class)]
    #[ORM\JoinColumn(name: "season_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Season $season = null;

    #[ORM\Column(name: "points_total", type: "integer", options: ["default" => 0])]
    private int $pointsTotal = 0;

    #[ORM\Column(name: "final_rank", type: "integer", nullable: true)]
    private ?int $finalRank = null;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $common = 0;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $uncommon = 0;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $rare = 0;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $epic = 0;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $legendary = 0;

    /** Plní DB (DEFAULT / ON UPDATE CURRENT_TIMESTAMP) */
    #[ORM\Column(name: "last_update_at", type: "datetime", insertable: false, updatable: false, generated: "ALWAYS", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $lastUpdateAt;

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function setFaction(Faction $faction): void
    {
        $this->faction = $faction;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }
    public function setSeason(?Season $season): void
    {
        $this->season = $season;
    }

    public function getSeasonId(): ?int
    {
        return $this->season?->getId();
    }

    public function getLastUpdateAt(): \DateTimeInterface
    {
        return $this->lastUpdateAt;
    }

    public function getPointsTotal(): int
    {
        return $this->pointsTotal;
    }

    public function setPointsTotal($pointsTotal): void
    {
        $this->pointsTotal = $pointsTotal;
    }

    public function getFinalRank(): ?int
    {
        return $this->finalRank;
    }

    public function setFinalRank($finalRank): void
    {
        $this->finalRank = $finalRank;
    }

    public function getCommon(): int
    {
        return $this->common;
    }

    public function setCommon(int $common): void
    {
        $this->common = $common;
    }

    public function getUncommon(): int
    {
        return $this->uncommon;
    }

    public function setUncommon(int $uncommon): void
    {
        $this->uncommon = $uncommon;
    }

    public function getRare(): int
    {
        return $this->rare;
    }

    public function setRare(int $rare): void
    {
        $this->rare = $rare;
    }

    public function getEpic(): int
    {
        return $this->epic;
    }

    public function setEpic(int $epic): void
    {
        $this->epic = $epic;
    }

    public function getLegendary(): int
    {
        return $this->legendary;
    }

    public function setLegendary(int $legendary): void
    {
        $this->legendary = $legendary;
    }

    public function addCard(array $cards): void
    {
        //TODO vymyslet přidávání karet jednou funkcí, ať nemá každý svůj vlastní add funkcion.
    }
}
