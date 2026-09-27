<?php declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Testovací karty pro zkoušení balíčků – každá frakce (vč. neutrální) dostane chybějící rarity C/U/R/E/L.
 * Jen pro vývojovou DB: `php vendor/bin/phinx seed:run -s TestCardSeeder`
 * NEspouštět na noctis_test – integrační testy si karty vytváří samy.
 * Opakované spuštění nic nezdvojí (přidá jen chybějící kombinace frakce × rarita).
 */
final class TestCardSeeder extends AbstractSeed
{
    private const RARITIES = [
        'C' => 'common',
        'U' => 'uncommon',
        'R' => 'rare',
        'E' => 'epic',
        'L' => 'legendary',
    ];

    public function run(): void
    {
        $database = (string) $this->getAdapter()->getOption('name');
        if (str_ends_with($database, '_test')) {
            $this->getOutput()->writeln("<comment>TestCardSeeder: přeskočeno pro testovací DB '$database'.</comment>");
            return;
        }

        $factions =$this->fetchAll("SELECT id, name FROM faction WHERE slug IN ('ignis', 'vitae', 'noctis', 'neutral')");

        $rows = [];
        foreach ($factions as $faction) {
            foreach (self::RARITIES as $code => $image) {
                $exists = $this->fetchRow(sprintf(
                    "SELECT 1 FROM card WHERE faction_id = %d AND rarity = '%s' LIMIT 1",
                    (int) $faction['id'],
                    $code,
                ));
                if ($exists) {
                    continue;
                }
                $rows[] = [
                    'faction_id' => (int) $faction['id'],
                    'name' => sprintf('Test %s %s', $faction['name'], ucfirst($image)),
                    'rarity' => $code,
                    'image_path' => "/assets/cards/$image.png",
                ];
            }
        }

        if ($rows !== []) {
            $this->table('card')->insert($rows)->saveData();
        }
    }
}
