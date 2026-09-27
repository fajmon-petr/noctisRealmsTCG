<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Kolekce karet hráče, batoh s balíčky a obsah otevřených balíčků.
 */
final class CreateCollectionAndBackpack extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            -- Kolekce: kolik kusů které karty hráč vlastní
            CREATE TABLE `player_card` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `player_id` int(11) NOT NULL,
              `card_id` int(11) NOT NULL,
              `quantity` int(10) unsigned NOT NULL DEFAULT 1,
              `obtained_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'první získání karty',
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_player_card` (`player_id`,`card_id`),
              KEY `idx_pc_card` (`card_id`),
              CONSTRAINT `fk_pc_player` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_pc_card` FOREIGN KEY (`card_id`) REFERENCES `card` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            -- Batoh: koupené balíčky (opened_at NULL = neotevřený)
            CREATE TABLE `player_pack` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `player_id` int(11) NOT NULL,
              `faction_id` int(11) NOT NULL COMMENT 'frakce balíčku',
              `purchased_at` datetime NOT NULL DEFAULT current_timestamp(),
              `opened_at` datetime DEFAULT NULL,
              `open_number` int(10) unsigned DEFAULT NULL COMMENT 'pořadí otevření u hráče (1, 2, …) – určuje garanci',
              `guarantee` char(1) DEFAULT NULL COMMENT 'garance při otevření (R/E/L), NULL = žádná',
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_pp_open_number` (`player_id`,`open_number`),
              KEY `idx_pp_player_opened` (`player_id`,`opened_at`),
              KEY `idx_pp_faction` (`faction_id`),
              KEY `idx_pp_guarantee` (`guarantee`),
              CONSTRAINT `fk_pp_player` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_pp_faction` FOREIGN KEY (`faction_id`) REFERENCES `faction` (`id`),
              CONSTRAINT `fk_pp_guarantee` FOREIGN KEY (`guarantee`) REFERENCES `rarity` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            -- Obsah otevřeného balíčku (výsledek, historie, data pro animaci)
            CREATE TABLE `player_pack_card` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `player_pack_id` int(11) NOT NULL,
              `card_id` int(11) NOT NULL,
              `slot` smallint(5) unsigned NOT NULL COMMENT 'pozice v balíčku 1–5',
              `guaranteed` tinyint(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_ppc_slot` (`player_pack_id`,`slot`),
              KEY `idx_ppc_card` (`card_id`),
              CONSTRAINT `fk_ppc_pack` FOREIGN KEY (`player_pack_id`) REFERENCES `player_pack` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_ppc_card` FOREIGN KEY (`card_id`) REFERENCES `card` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE `player_pack_card`, `player_pack`, `player_card`;');
    }
}
