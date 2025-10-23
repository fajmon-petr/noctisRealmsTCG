<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "faction_achievement")]
#[ORM\UniqueConstraint(name: "uq_faction_achievement", columns: ["faction_id", "achievement_id"])]
class FactionAchievement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: "faction_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Faction $faction;

    #[ORM\ManyToOne(targetEntity: Achievement::class)]
    #[ORM\JoinColumn(name: "achievement_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Achievement $achievement;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $achievedAt;

    public function __construct(Faction $faction, Achievement $achievement, ?\DateTimeInterface $at = null)
    {
        $this->faction  = $faction;
        $this->achievement = $achievement;
        $this->achievedAt  = $at ?? new \DateTimeImmutable();
    }

    // getters...
}
