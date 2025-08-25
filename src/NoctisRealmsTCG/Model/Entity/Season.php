<?php declare(strict_types=1);

namespace App\Model\Entity;

use App\Utils\MagicAccessors;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "seasons",
    uniqueConstraints: [new ORM\UniqueConstraint(name: "uniq_season_number", columns: ["number"])],
    indexes: [new ORM\Index(name: "idx_season_dates", columns: ["start_at", "end_at"])]
)]
class Season
{
    use MagicAccessors;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $number;

    #[ORM\Column(type: "string", length: 64)]
    private string $name;

    #[ORM\Column(name: "start_at", type: "datetime_immutable")]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(name: "end_at", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function getNumber(): int
    {
        return $this->number;
    }
    public function setNumber(int $n): void
    {
        $this->number = $n;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $n): void
    {
        $this->name = $n;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }
    public function setStartAt(\DateTimeImmutable $d): void
    {
        $this->startAt = $d;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }
    public function setEndAt(?\DateTimeImmutable $d): void
    {
        $this->endAt = $d;
    }

    public function isActive(?\DateTimeInterface $at = null): bool
    {
        $at ??= new \DateTimeImmutable();
        return $this->startAt <= $at && ($this->endAt === null || $this->endAt >= $at);
    }

    public function label(): string
    {
        return sprintf('S%d — %s', $this->number, $this->name);
    }
}
