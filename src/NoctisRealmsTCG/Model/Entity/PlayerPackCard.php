<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Karta, která padla v otevřeném balíčku */
#[ORM\Entity]
#[ORM\Table(name: "player_pack_card")]
#[ORM\UniqueConstraint(name: "uq_ppc_slot", columns: ["player_pack_id", "slot"])]
#[ORM\Index(name: "idx_ppc_card", columns: ["card_id"])]
class PlayerPackCard
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: PlayerPack::class, inversedBy: "cards")]
    #[ORM\JoinColumn(name: "player_pack_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private PlayerPack $pack;

    #[ORM\ManyToOne(targetEntity: Card::class, fetch: "EAGER")]
    #[ORM\JoinColumn(name: "card_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Card $card;

    /** Pozice v balíčku 1–5 */
    #[ORM\Column(type: "smallint", options: ["unsigned" => true, "comment" => "pozice v balíčku 1–5"])]
    private int $slot;

    #[ORM\Column(type: "boolean", options: ["default" => 0])]
    private bool $guaranteed = false;

    public function __construct(PlayerPack $pack, Card $card, int $slot, bool $guaranteed)
    {
        $this->pack = $pack;
        $this->card = $card;
        $this->slot = $slot;
        $this->guaranteed = $guaranteed;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPack(): PlayerPack
    {
        return $this->pack;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function getSlot(): int
    {
        return $this->slot;
    }

    public function isGuaranteed(): bool
    {
        return $this->guaranteed;
    }
}
