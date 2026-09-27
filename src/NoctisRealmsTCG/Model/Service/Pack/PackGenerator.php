<?php declare(strict_types=1);

namespace App\Model\Service\Pack;

use Random\Randomizer;

/**
 * Losování rarit karet v balíčku.
 * Čistá logika bez DB – základní šance dostane zvenku (z tabulky `rarity`),
 * náhodu přes Randomizer (v testech se seedem → deterministické).
 */
class PackGenerator
{
    private Randomizer $randomizer;

    public function __construct(?Randomizer $randomizer = null)
    {
        $this->randomizer = $randomizer ?? new Randomizer(); // výchozí engine je kryptograficky bezpečný
    }

    /**
     * Vylosuje rarity pro N-tý otevřený balíček hráče.
     * Balíček s garancí má garantovaný slot jako poslední (odhalí se na konec).
     *
     * @param array<string, float|int> $baseChances kód rarity => základní šance (%)
     * @return list<PackSlot>
     */
    public function generate(int $openNumber, array $baseChances): array
    {
        $guarantee = PackRules::guaranteeFor($openNumber);
        $regularCount = PackRules::CARDS_PER_PACK - ($guarantee !== null ? 1 : 0);

        $slots = [];
        for ($i = 0; $i < $regularCount; $i++) {
            $slots[] = new PackSlot($this->pick($baseChances));
        }

        if ($guarantee !== null) {
            $slots[] = new PackSlot($this->pick(PackRules::GUARANTEE_CHANCES[$guarantee]), guaranteed: true);
        }

        return $slots;
    }

    /**
     * Náhodný prvek seznamu (rovnoměrně) – např. konkrétní karta z vylosované rarity.
     *
     * @template T
     * @param list<T> $items
     * @return T
     */
    public function pickRandom(array $items): mixed
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Není z čeho vybírat.');
        }
        return $items[$this->randomizer->getInt(0, count($items) - 1)];
    }

    /**
     * Vážený výběr podle šancí. Šance nemusí dávat součet 100 – berou se poměrově.
     *
     * @param array<string, float|int> $chances kód rarity => šance
     */
    public function pick(array $chances): string
    {
        // na celá čísla (setiny procenta), aby losování nemělo chyby zaokrouhlení floatů
        $weights = [];
        foreach ($chances as $rarity => $chance) {
            if ($chance < 0) {
                throw new \InvalidArgumentException("Šance rarity '$rarity' nesmí být záporná.");
            }
            $weights[(string) $rarity] = (int) round($chance * 100);
        }

        $total = array_sum($weights);
        if ($total <= 0) {
            throw new \InvalidArgumentException('Součet šancí musí být větší než 0.');
        }

        $roll = $this->randomizer->getInt(1, $total);
        foreach ($weights as $rarity => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $rarity;
            }
        }

        throw new \LogicException('Nedosažitelné – vážený výběr nic nevybral.');
    }
}
