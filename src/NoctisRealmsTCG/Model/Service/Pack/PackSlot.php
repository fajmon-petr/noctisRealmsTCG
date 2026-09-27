<?php declare(strict_types=1);

namespace App\Model\Service\Pack;

/** Jeden slot (karta) vylosovaného balíčku – zatím jen rarita, konkrétní kartu dosadí fasáda */
final readonly class PackSlot
{
    public function __construct(
        /** Kód rarity (C/U/R/E/L) */
        public string $rarity,
        /** Slot vznikl z garance (5./10./20. balíček) */
        public bool $guaranteed = false,
    ) {}
}
