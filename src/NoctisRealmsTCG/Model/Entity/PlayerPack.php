<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Balíček v batohu hráče – koupený, případně už otevřený */
#[ORM\Entity]
#[ORM\Table(name: "player_pack")]
#[ORM\UniqueConstraint(name: "uq_pp_open_number", columns: ["player_id", "open_number"])]
#[ORM\Index(name: "idx_pp_player_opened", columns: ["player_id", "opened_at"])]
#[ORM\Index(name: "idx_pp_faction", columns: ["faction_id"])]
#[ORM\Index(name: "idx_pp_guarantee", columns: ["guarantee"])]
class PlayerPack
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: "player_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Player $player;

    /** Frakce balíčku – padají karty této frakce + neutrální */
    #[ORM\ManyToOne(targetEntity: Faction::class, fetch: "EAGER")]
    #[ORM\JoinColumn(name: "faction_id", referencedColumnName: "id", nullable: false, options: ["comment" => "frakce balíčku"])]
    private Faction $faction;

    #[ORM\Column(name: "purchased_at", type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeImmutable $purchasedAt;

    #[ORM\Column(name: "opened_at", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $openedAt = null;

    /** Pořadí otevření u hráče (1, 2, …) – určuje garanci */
    #[ORM\Column(name: "open_number", type: "integer", nullable: true, options: ["unsigned" => true, "comment" => "pořadí otevření u hráče (1, 2, …) – určuje garanci"])]
    private ?int $openNumber = null;

    /** Garance, která platila při otevření (R/E/L), null = žádná */
    #[ORM\ManyToOne(targetEntity: Rarity::class)]
    #[ORM\JoinColumn(name: "guarantee", referencedColumnName: "code", nullable: true, options: ["comment" => "garance při otevření (R/E/L), NULL = žádná"])]
    private ?Rarity $guarantee = null;

    /** @var Collection<int, PlayerPackCard> */
    #[ORM\OneToMany(targetEntity: PlayerPackCard::class, mappedBy: "pack", cascade: ["persist"])]
    #[ORM\OrderBy(["slot" => "ASC"])]
    private Collection $cards;

    public function __construct(Player $player, Faction $faction)
    {
        $this->player = $player;
        $this->faction = $faction;
        $this->purchasedAt = new \DateTimeImmutable();
        $this->cards = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function getPurchasedAt(): \DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function getOpenedAt(): ?\DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function isOpened(): bool
    {
        return $this->openedAt !== null;
    }

    public function getOpenNumber(): ?int
    {
        return $this->openNumber;
    }

    public function getGuarantee(): ?Rarity
    {
        return $this->guarantee;
    }

    /** @return Collection<int, PlayerPackCard> */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    /** Označí balíček jako otevřený; karty se přidají přes addCard() */
    public function open(int $openNumber, ?Rarity $guarantee): void
    {
        if ($this->isOpened()) {
            throw new \LogicException("Balíček {$this->id} už je otevřený.");
        }
        $this->openNumber = $openNumber;
        $this->guarantee = $guarantee;
        $this->openedAt = new \DateTimeImmutable();
    }

    public function addCard(Card $card, int $slot, bool $guaranteed): PlayerPackCard
    {
        $packCard = new PlayerPackCard($this, $card, $slot, $guaranteed);
        $this->cards->add($packCard);
        return $packCard;
    }
}
