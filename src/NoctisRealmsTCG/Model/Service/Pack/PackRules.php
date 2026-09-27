<?php declare(strict_types=1);

namespace App\Model\Service\Pack;

use App\Model\Entity\Rarity;
use Nette\StaticClass;

/**
 * Pravidla balíčků. Základní drop šance jsou v DB (tabulka `rarity`),
 * garance se řídí pořadím otevřeného balíčku hráče.
 */
final class PackRules
{
    use StaticClass;

    /** Cena jednoho balíčku v Moon Dustu */
    public const PRICE = 100;

    /** Počet karet v balíčku */
    public const CARDS_PER_PACK = 5;

    /** Každý N-tý otevřený balíček má garantovaný slot (vyšší garance má přednost) */
    public const GUARANTEE_EVERY = [
        Rarity::LEGENDARY => 20,
        Rarity::EPIC => 10,
        Rarity::RARE => 5,
    ];

    /**
     * Šance v % uvnitř garantovaného slotu podle typu garance.
     * Legendary padá hlavně z běžných karet a z 20. balíčku, ne z garancí rare+/epic+
     * (cíl ~1,5 legendary / ~3 epic / ~6 rare na 20 balíčků – varianta B, 2026-09-27).
     */
    public const GUARANTEE_CHANCES = [
        Rarity::RARE => [Rarity::RARE => 90, Rarity::EPIC => 10],
        Rarity::EPIC => [Rarity::EPIC => 100],
        Rarity::LEGENDARY => [Rarity::LEGENDARY => 100],
    ];

    /**
     * Garance pro N-tý otevřený balíček hráče (počítáno od 1).
     * Platí vždy nejvýš jedna: 20. legendary, jinak 10. epic+, jinak 5. rare+.
     *
     * @return string|null kód minimální rarity garantovaného slotu, null = bez garance
     */
    public static function guaranteeFor(int $openNumber): ?string
    {
        if ($openNumber < 1) {
            throw new \InvalidArgumentException("Pořadí balíčku musí být alespoň 1, zadáno $openNumber.");
        }

        foreach (self::GUARANTEE_EVERY as $rarity => $every) {
            if ($openNumber % $every === 0) {
                return $rarity;
            }
        }

        return null;
    }

    /**
     * Kolik balíčků ještě hráč otevře, než dostane danou garanci (včetně toho s garancí).
     * Např. po 3 otevřených: rare+ za 2, epic+ za 7, legendary za 17.
     */
    public static function packsUntilGuarantee(int $openedPacks, string $guarantee): int
    {
        if (!isset(self::GUARANTEE_EVERY[$guarantee])) {
            throw new \InvalidArgumentException("Neznámá garance '$guarantee'.");
        }

        $max = max(self::GUARANTEE_EVERY);
        for ($n = max(0, $openedPacks) + 1, $i = 1; $i <= $max; $n++, $i++) {
            if (self::guaranteeFor($n) === $guarantee) {
                return $i;
            }
        }

        throw new \LogicException("Garance '$guarantee' se v rozvrhu nikdy nevyskytuje.");
    }
}
