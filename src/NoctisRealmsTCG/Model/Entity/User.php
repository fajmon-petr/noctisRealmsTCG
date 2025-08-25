<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Utils\MagicAccessors;

/**
 * @property-read int|null $id
 * @property string $email
 * @property string $password
 * @property Role|null $role
 */
#[ORM\Entity]
#[ORM\Table(name: "users")]
class User
{
    use MagicAccessors;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", unique: true)]
    private string $email;

    #[ORM\Column(type: "string")]
    private string $password;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: "role_id", referencedColumnName: "id", nullable: false, onDelete: "RESTRICT")]
    private ?Role $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }
    public function setRole(Role $role): void
    {
        $this->role = $role;
    }
}
