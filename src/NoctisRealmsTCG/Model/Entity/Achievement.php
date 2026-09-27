<?php declare(strict_types=1);

namespace App\Model\Entity;

use App\Utils\MagicAccessors;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "achievement")]
#[ORM\UniqueConstraint(name: "uq_achievement_code", columns: ["code"])]
class Achievement
{
    use MagicAccessors;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $code;

    #[ORM\Column(type: "string", length: 150)]
    private string $name;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $points = 0;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $type;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct(
        string  $code,
        string  $name,
        ?string $description = null,
        int     $points = 0,
        ?string $icon = null,
        string  $type = 'created'
    )
    {
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->points = $points;
        $this->icon = $icon;
        $this->type = $type;
        $this->createdAt = new \DateTimeImmutable();
    }

    // === Getters ===
    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    // === Setters ===
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
