<?php declare(strict_types=1);

namespace App\Model\Entity;

use App\Utils\MagicAccessors;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "faction_season_stat",
    uniqueConstraints: [new ORM\UniqueConstraint(name: "uniq_faction_season", columns: ["faction_id", "season_id"])]
)]
class FactionSeasonStats
{
    use MagicAccessors;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: "faction_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Faction $faction;

    #[ORM\ManyToOne(targetEntity: Season::class)]
    #[ORM\JoinColumn(name: "season_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Season $season = null;

    // body a pořadí
    #[ORM\Column(name: "points_total", type: "integer")]
    private int $pointsTotal = 0;

    #[ORM\Column(name: "final_rank", type: "integer", nullable: true)]
    private ?int $finalRank = null;

    // rozpad podle rarity
    #[ORM\Column(type: "integer")]
    private int $common = 0;

    #[ORM\Column(type: "integer")]
    private int $uncommon = 0;

    #[ORM\Column(type: "integer")]
    private int $rare = 0;

    #[ORM\Column(type: "integer")]
    private int $epic = 0;

    #[ORM\Column(type: "integer")]
    private int $legendary = 0;

    #[ORM\Column(name: "last_update_at", type: "datetime")]
    private \DateTimeInterface $lastUpdateAt;

    // ---- getters / setters ----

    public function getId(): int
    {
        return $this->id;
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

    public function getPointsTotal(): int
    {
        return $this->pointsTotal;
    }

    public function setPointsTotal(int $pointsTotal): void
    {
        $this->pointsTotal = $pointsTotal;
    }

    public function getFinalRank(): ?int
    {
        return $this->finalRank;
    }

    public function setFinalRank(?int $finalRank): void
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

    public function getLastUpdateAt(): \DateTimeInterface
    {
        return $this->lastUpdateAt;
    }

    public function setLastUpdateAt(\DateTimeInterface $dt): void
    {
        $this->lastUpdateAt = $dt;
    }
}
