<?php declare(strict_types=1);

namespace App\Model\Entity;

use App\Utils\MagicAccessors;
use Doctrine\ORM\Mapping as ORM;

/**
 * Rarita karty (číselník). Šance a hodnoty se ladí přímo v DB.
 *
 * @property-read string $code
 * @property-read string $name
 * @property-read int $sortOrder
 * @property-read float $dropChance
 * @property-read int $donationValue
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: "rarity")]
#[ORM\UniqueConstraint(name: "uq_rarity_sort_order", columns: ["sort_order"])]
class Rarity
{
    use MagicAccessors;

    public const COMMON = 'C';
    public const UNCOMMON = 'U';
    public const RARE = 'R';
    public const EPIC = 'E';
    public const LEGENDARY = 'L';

    #[ORM\Id]
    #[ORM\Column(type: "string", length: 1, options: ["fixed" => true])]
    private string $code;

    #[ORM\Column(type: "string", length: 32)]
    private string $name;

    /** Pořadí od nejběžnější (1 = common) po nejvzácnější */
    #[ORM\Column(name: "sort_order", type: "smallint", options: ["unsigned" => true])]
    private int $sortOrder;

    /** Základní šance v % na jednu kartu balíčku */
    #[ORM\Column(name: "drop_chance", type: "decimal", precision: 5, scale: 2, options: ["unsigned" => true, "comment" => "základní šance v % na jednu kartu balíčku"])]
    private string $dropChance;

    /** Moon Dust hráči a body frakci za darovanou kartu */
    #[ORM\Column(name: "donation_value", type: "integer", options: ["unsigned" => true, "comment" => "Moon Dust hráči a body frakci za darovanou kartu"])]
    private int $donationValue;

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getDropChance(): float
    {
        return (float) $this->dropChance;
    }

    public function getDonationValue(): int
    {
        return $this->donationValue;
    }
}
