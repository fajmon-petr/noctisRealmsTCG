<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Utils\MagicAccessors;

/**
 * @property-read int $id
 * @property \App\Model\Entity\User $user
 * @property string|null $nickname
 * @property \App\Model\Entity\Faction|null $faction
 * @property int $cards
 * @property int $openedPacks
 * @property int $achievements
 * @property int $level
 * @property int $xp
 * @property int $moonDust
 * @property string|null $avatar
 */
#[ORM\Entity]
#[ORM\Table(name: "profiles")]
class Profile
{
    use MagicAccessors;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private User $user;

    #[ORM\Column(type: "string", length: 64, nullable: true)]
    private ?string $nickname = null;

    #[ORM\ManyToOne(targetEntity: Faction::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: "faction_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Faction $faction = null;

    #[ORM\Column(type: "integer")]
    private int $cards = 0;

    #[ORM\Column(type: "integer", name: "opened_packs")]
    private int $openedPacks = 0;

    #[ORM\Column(type: "integer", name: "achievements")]
    private int $achievements = 0;

    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $level = 1;

    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $xp = 0;

    #[ORM\Column(name: "moon_dust", type: "integer", options: ["unsigned" => true])]
    private int $moonDust = 0;

    #[ORM\Column(type: "string", length: 64, nullable: true)]
    private ?string $avatar = null;


    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }
    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getFaction(): ?Faction
    {
        return $this->faction;
    }
    public function setFaction(?Faction $f): void
    {
        $this->faction = $f;
    }

    public function getCards(): int
    {
        return $this->cards;
    }
    public function setCards(int $n): void
    {
        $this->cards = $n;
    }

    public function getOpenedPacks(): int
    {
        return $this->openedPacks;
    }
    public function setOpenedPacks(int $n): void
    {
        $this->openedPacks = $n;
    }

    public function getAchievements(): int
    {
        return $this->achievements;
    }
    public function setAchievements(int $n): void
    {
        $this->achievements = $n;
    }

    public function getLevel(): int
    {
        return $this->level;
    }
    public function setLevel(int $l): void
    {
        $this->level = max(1, $l);
    }

    public function getXp(): int
    {
        return $this->xp;
    }
    public function setXp(int $x): void
    {
        $this->xp = max(0, $x);
    }

    public function getMoonDust(): int
    {
        return $this->moonDust;
    }
    public function setMoonDust(int $md): void
    {
        $this->moonDust = max(0, $md);
    }
    public function addMoonDust(int $md): void
    {
        $this->moonDust = max(0, $this->moonDust + $md);
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }
}
