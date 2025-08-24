<?php declare(strict_types=1);

namespace App\Model\Service;

use App\Model\Entity\Profile;

class LevelingService
{
    /** XP potřeba na přechod z aktuálního levelu na další */
    public function thresholdFor(int $level): int
    {
        // hladká křivka: 100 * level^1.5 (př.: L1→L2 = 100, L5→L6 ≈ 1118)
        return (int) round(100 * pow(max(1, $level), 1.5));
    }

    /** Přidá XP a případně zvedne level (umí víc levelů naráz) */
    public function addXp(Profile $p, int $gain): void
    {
        $xp = $p->getXp() + max(0, $gain);
        $lvl = $p->getLevel();

        while ($xp >= ($need = $this->thresholdFor($lvl))) {
            $xp -= $need;
            $lvl++;
        }
        $p->setXp($xp);
        $p->setLevel($lvl);
    }

    /** Kolik % do dalšího levelu */
    public function percent(Profile $p): int
    {
        $need = $this->thresholdFor($p->getLevel());
        return (int) floor(($p->getXp() / max(1, $need)) * 100);
    }
}
