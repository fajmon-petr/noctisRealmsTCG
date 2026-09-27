<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'card')]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'faction_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Faction $faction = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'string', length: 1)]
    private string $rarity; // C, U, R, E, L

    #[ORM\Column(type: 'string', length: 255)]
    private string $imagePath; // např. '/assets/cards/ignis-001.png'

    public function __construct(string $name, string $rarity, string $imagePath)
    {
        $this->name = $name;
        $this->rarity = $rarity;
        $this->imagePath = $imagePath;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFaction(): ?Faction
    {
        return $this->faction;
    }

    public function setFaction(Faction $faction): self
    {
        $this->faction = $faction;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): self
    {
        $this->rarity = $rarity;
        return $this;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $path): self
    {
        $this->imagePath = $path;
        return $this;
    }
}
