<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @property-read int $id */
#[ORM\Entity]
#[ORM\Table(name: "roles")]
class Role
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 32, unique: true)]
    private string $slug;

    #[ORM\Column(type: "string", length: 64)]
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $s): void
    {
        $this->slug = $s;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $n): void
    {
        $this->name = $n;
    }
}
