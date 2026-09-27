<?php declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/** Sezóny – Alpha je uzavřená, Beta probíhá */
final class SeasonSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->table('season')->insert([
            ['id' => 1, 'number' => 1, 'name' => 'Alpha', 'start_at' => '2025-01-01 00:00:00', 'end_at' => '2025-03-31 23:59:59'],
            ['id' => 2, 'number' => 2, 'name' => 'Beta', 'start_at' => '2025-04-01 00:00:00', 'end_at' => null],
        ])->saveData();
    }
}
