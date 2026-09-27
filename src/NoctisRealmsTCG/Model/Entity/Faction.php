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
 * @property string|null $emblem
 * @property string|null $banner
 * @property string|null $perk
 * @property bool $selectable
 * @property int $playersCount
 * @property int $factionPoints
 */
#[ORM\Entity]
#[ORM\Table(name: "faction")]
#[ORM\UniqueConstraint(name: "UNIQ_factions_slug", columns: ["slug"])]
#[ORM\Index(name: "IDX_factions_selectable", columns: ["is_selectable"])]
class Faction
{
    use MagicAccessors;

    /** Neutrální frakce – nedá se zvolit, její karty padají ve všech balíčcích */
    public const NEUTRAL_SLUG = 'neutral';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 32)]
    private string $slug;

    #[ORM\Column(type: "string", length: 64)]
    private string $name;

    #[ORM\Column(type: "string", length: 7, options: ["fixed" => true])]
    private string $color;

    #[ORM\Column(type: "text", length: 65535, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $emblem = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $banner = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $perk = null;

    /** Lze frakci zvolit při registraci (neutrální ne) */
    #[ORM\Column(name: "is_selectable", type: "boolean", options: ["default" => 1])]
    private bool $selectable = true;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $playersCount = 0;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
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

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): void
    {
        $this->banner = $banner;
    }

    public function getPerk(): ?string
    {
        return $this->perk;
    }

    public function setPerk(?string $perk): void
    {
        $this->perk = $perk;
    }

    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    public function setSelectable(bool $selectable): void
    {
        $this->selectable = $selectable;
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
