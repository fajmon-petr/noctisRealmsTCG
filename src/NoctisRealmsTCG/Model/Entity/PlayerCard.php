<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Karta v kolekci hráče (s počtem kusů) */
#[ORM\Entity]
#[ORM\Table(name: "player_card")]
#[ORM\UniqueConstraint(name: "uq_player_card", columns: ["player_id", "card_id"])]
#[ORM\Index(name: "idx_pc_card", columns: ["card_id"])]
class PlayerCard
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: "player_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Card::class, fetch: "EAGER")]
    #[ORM\JoinColumn(name: "card_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Card $card;

    #[ORM\Column(type: "integer", options: ["unsigned" => true, "default" => 1])]
    private int $quantity = 1;

    #[ORM\Column(name: "obtained_at", type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP", "comment" => "první získání karty"])]
    private \DateTimeImmutable $obtainedAt;

    public function __construct(Player $player, Card $card)
    {
        $this->player = $player;
        $this->card = $card;
        $this->obtainedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /** Kolik kusů lze darovat – poslední kus si hráč vždy nechá */
    public function getDonatableQuantity(): int
    {
        return max(0, $this->quantity - 1);
    }

    public function getObtainedAt(): \DateTimeImmutable
    {
        return $this->obtainedAt;
    }

    public function add(int $count = 1): void
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Počet přidaných karet musí být alespoň 1.');
        }
        $this->quantity += $count;
    }

    /** Odebere kusy; poslední kus odebrat nejde */
    public function remove(int $count): void
    {
        if ($count < 1 || $count > $this->getDonatableQuantity()) {
            throw new \InvalidArgumentException(
                "Nelze odebrat $count ks – k dispozici je {$this->getDonatableQuantity()} ks (poslední kus zůstává).",
            );
        }
        $this->quantity -= $count;
    }
}
