<?php declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/** Tři hratelné frakce + neutrální (nevolitelná) */
final class FactionSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->table('faction')->insert([
            [
                'id' => 1,
                'slug' => 'ignis',
                'name' => 'Ignis',
                'color' => '#E53935',
                'description' => 'Oheň a síla.',
                'emblem' => '/assets/factions/ignis.png',
                'perk' => 'Bonus k útoku po výhře.',
                'is_selectable' => 1,
            ],
            [
                'id' => 2,
                'slug' => 'vitae',
                'name' => 'Vitae',
                'color' => '#43A047',
                'description' => 'Léčení a růst.',
                'emblem' => '/assets/factions/vitae.png',
                'perk' => 'Malé léčení po kole.',
                'is_selectable' => 1,
            ],
            [
                'id' => 3,
                'slug' => 'noctis',
                'name' => 'Noctis',
                'color' => '#6A1B9A',
                'description' => 'Temnota a kontrola.',
                'emblem' => '/assets/factions/noctis.png',
                'perk' => 'Šance zablokovat efekt.',
                'is_selectable' => 1,
            ],
            [
                'id' => 4,
                'slug' => 'neutral',
                'name' => 'Neutral',
                'color' => '#999999',
                'description' => 'Neutrální síla – spojení všech elementů.',
                'emblem' => '/assets/factions/neutral.png',
                'perk' => 'Žádný bonus, čistá rovnováha.',
                'is_selectable' => 0,
            ],
        ])->saveData();
    }
}
