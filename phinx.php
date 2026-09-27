<?php declare(strict_types=1);

/**
 * Konfigurace Phinx migrací.
 * Připojení k DB se čte z config/doctrine.neon, aby bylo nastavené jen na jednom místě.
 *
 *   php vendor/bin/phinx migrate                 # development (noctis)
 *   php vendor/bin/phinx migrate -e testing      # testovací DB (noctis_test)
 *   php vendor/bin/phinx seed:run -e testing     # základní data
 */

use Nette\Neon\Neon;

require_once __DIR__ . '/vendor/autoload.php';

$db = Neon::decodeFile(__DIR__ . '/config/doctrine.neon')['doctrine.dbal']['connections']['default'];

$environment = static fn(string $dbName): array => [
    'adapter' => 'mysql',
    'host' => $db['host'],
    'port' => $db['port'] ?? 3306,
    'name' => $dbName,
    'user' => $db['user'],
    'pass' => $db['password'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
];

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => $environment($db['dbname']),
        'testing' => $environment($db['dbname'] . '_test'),
    ],
    'version_order' => 'creation',
];
