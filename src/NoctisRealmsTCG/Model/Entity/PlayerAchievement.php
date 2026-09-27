<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "player_achievement")]
#[ORM\UniqueConstraint(name: "uq_player_achievement", columns: ["player_id", "achievement_id"])]
#[ORM\Index(name: "idx_pa_player", columns: ["player_id"])]
#[ORM\Index(name: "idx_pa_achievement", columns: ["achievement_id"])]
#[ORM\Index(name: "idx_pa_achieved_at", columns: ["achieved_at"])]
class PlayerAchievement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: "player_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Achievement::class)]
    #[ORM\JoinColumn(name: "achievement_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Achievement $achievement;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $achievedAt;

    public function __construct(Player $player, Achievement $achievement, ?\DateTimeInterface $at = null)
    {
        $this->player = $player;
        $this->achievement = $achievement;
        $this->achievedAt = $at ?? new \DateTimeImmutable();
    }

    // getters...
}
