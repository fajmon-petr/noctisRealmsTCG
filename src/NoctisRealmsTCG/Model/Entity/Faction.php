<?php declare(strict_types=1);

namespace App\Model\Entity;

use App\Utils\MagicAccessors;
use Doctrine\ORM\Mapping as ORM;

/**
 * @property-read int $id
 * @property string $slug
 * @property string $name
 * @property string $color
 * @property string|null $description
 * @property int $playersCount
 * @property int $factionPoints
 */
#[ORM\Entity]
#[ORM\Table(name: "faction")]
class Faction
{
    use MagicAccessors;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 32, unique: true)]
    private string $slug;

    #[ORM\Column(type: "string", length: 64)]
    private string $name;

    #[ORM\Column(type: "string", length: 7, options: ["fixed" => true])]
    private string $color;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $emblem = null;

    #[ORM\Column(type: "integer")]
    private int $playersCount = 0;

    #[ORM\Column(type: "integer")]
    private int $factionPoints = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $d): void
    {
        $this->description = $d;
    }

    public function getEmblem(): ?string
    {
        return $this->emblem;
    }

    public function setEmblem(?string $emblem): void
    {
        $this->emblem = $emblem;
    }

    public function getPlayersCount(): int
    {
        return $this->playersCount;
    }

    public function setPlayersCount(int $n): void
    {
        $this->playersCount = $n;
    }

    public function getFactionPoints(): int
    {
        return $this->factionPoints;
    }

    public function setFactionPoints(int $n): void
    {
        $this->factionPoints = $n;
    }
}
