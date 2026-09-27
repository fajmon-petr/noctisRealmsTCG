<?php declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/** Definice achievementů */
final class AchievementSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->table('achievement')->insert([
            ['id' => 1, 'code' => 'OPEN_1_PACK', 'name' => 'První balíček', 'description' => 'Otevři první balíček', 'points' => 10, 'type' => null],
            ['id' => 2, 'code' => 'OPEN_10_PACKS', 'name' => 'Balíčkář', 'description' => 'Otevři 10 balíčků', 'points' => 50, 'type' => null],
            ['id' => 3, 'code' => 'WIN_1_MATCH', 'name' => 'První výhra', 'description' => 'Vyhraj zápas', 'points' => 20, 'type' => null],
            ['id' => 4, 'code' => 'WIN_10_MATCH', 'name' => 'Jedeš bomby', 'description' => 'Vyhraj 10 zápasů', 'points' => 80, 'type' => null],
            ['id' => 5, 'code' => 'DONATE_DUST', 'name' => 'Podpora frakce', 'description' => 'Přispěj Dustem', 'points' => 15, 'type' => 'faction'],
        ])->saveData();
    }
}
