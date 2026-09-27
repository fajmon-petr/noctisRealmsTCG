<?php declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/** Uživatelské role */
final class RoleSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->table('role')->insert([
            ['id' => 1, 'slug' => 'player', 'name' => 'Hráč'],
            ['id' => 2, 'slug' => 'admin', 'name' => 'Administrátor'],
        ])->saveData();
    }
}
