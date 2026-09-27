<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Výchozí stav schématu (baseline) – přesná kopie DB `noctis` k 2026-09-27.
 * Nahrazuje ruční SQL migrace v db/legacy/. Na existující DB se označí jako provedená
 * (`phinx migrate --fake`), na nové DB (např. testovací) schéma vytvoří.
 */
final class InitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE `role` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `slug` varchar(32) NOT NULL,
              `name` varchar(64) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            CREATE TABLE `user` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `email` varchar(255) NOT NULL,
              `password` varchar(255) NOT NULL,
              `role_id` int(10) unsigned NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `email` (`email`),
              KEY `fk_users_role` (`role_id`),
              CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE `faction` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `slug` varchar(32) NOT NULL,
              `name` varchar(64) NOT NULL,
              `color` char(7) NOT NULL,
              `description` text DEFAULT NULL,
              `emblem` varchar(255) DEFAULT NULL,
              `banner` varchar(255) DEFAULT NULL,
              `perk` varchar(255) DEFAULT NULL,
              `is_selectable` tinyint(1) NOT NULL DEFAULT 1,
              `players_count` int(11) NOT NULL DEFAULT 0,
              `faction_points` int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNIQ_factions_slug` (`slug`),
              KEY `IDX_factions_selectable` (`is_selectable`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

            CREATE TABLE `season` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `number` int(11) NOT NULL,
              `name` varchar(64) NOT NULL,
              `start_at` datetime NOT NULL,
              `end_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `number` (`number`),
              KEY `idx_season_dates` (`start_at`,`end_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            CREATE TABLE `player` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `nickname` varchar(64) DEFAULT NULL,
              `cards` int(11) NOT NULL DEFAULT 0,
              `opened_packs` int(11) NOT NULL DEFAULT 0,
              `achievements` int(11) NOT NULL DEFAULT 0,
              `faction_id` int(11) DEFAULT NULL,
              `level` int(11) NOT NULL DEFAULT 1,
              `xp` int(11) NOT NULL DEFAULT 0,
              `moon_dust` int(10) unsigned NOT NULL DEFAULT 0,
              `avatar` varchar(100) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNIQ_profiles_user` (`user_id`),
              KEY `IDX_profiles_user` (`user_id`),
              KEY `fk_profiles_faction` (`faction_id`),
              CONSTRAINT `FK_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_profiles_faction` FOREIGN KEY (`faction_id`) REFERENCES `faction` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

            CREATE TABLE `card` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `faction_id` int(11) DEFAULT NULL,
              `name` varchar(120) NOT NULL,
              `rarity` char(1) NOT NULL,
              `image_path` varchar(255) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `fk_card_faction` (`faction_id`),
              CONSTRAINT `fk_card_faction` FOREIGN KEY (`faction_id`) REFERENCES `faction` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            CREATE TABLE `achievement` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `code` varchar(100) NOT NULL,
              `name` varchar(150) NOT NULL,
              `description` text DEFAULT NULL,
              `points` int(11) NOT NULL DEFAULT 0,
              `icon` varchar(255) DEFAULT NULL,
              `type` varchar(255) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_achievement_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE `player_season_stat` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `player_id` int(11) NOT NULL,
              `faction_id` int(11) NOT NULL,
              `season_id` int(11) DEFAULT NULL,
              `points_total` int(11) NOT NULL DEFAULT 0,
              `final_rank` int(11) DEFAULT NULL,
              `common` int(11) NOT NULL DEFAULT 0,
              `uncommon` int(11) NOT NULL DEFAULT 0,
              `rare` int(11) NOT NULL DEFAULT 0,
              `epic` int(11) NOT NULL DEFAULT 0,
              `legendary` int(11) NOT NULL DEFAULT 0,
              `last_update_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq_player_faction_season` (`player_id`,`faction_id`,`season_id`),
              KEY `idx_leaderboard` (`faction_id`,`points_total`),
              KEY `idx_pfs_season_points` (`season_id`,`points_total`),
              CONSTRAINT `fk_pfs_faction` FOREIGN KEY (`faction_id`) REFERENCES `faction` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_pfs_profile` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_pfs_season` FOREIGN KEY (`season_id`) REFERENCES `season` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            CREATE TABLE `faction_season_stat` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `faction_id` int(11) NOT NULL,
              `season_id` int(11) NOT NULL,
              `points_total` int(11) NOT NULL DEFAULT 0,
              `final_rank` int(11) DEFAULT NULL,
              `common` int(11) NOT NULL DEFAULT 0,
              `uncommon` int(11) NOT NULL DEFAULT 0,
              `rare` int(11) NOT NULL DEFAULT 0,
              `epic` int(11) NOT NULL DEFAULT 0,
              `legendary` int(11) NOT NULL DEFAULT 0,
              `last_update_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `idx_faction_season` (`faction_id`,`season_id`),
              KEY `fk_fss_season` (`season_id`),
              CONSTRAINT `fk_fss_faction` FOREIGN KEY (`faction_id`) REFERENCES `faction` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_fss_season` FOREIGN KEY (`season_id`) REFERENCES `season` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            CREATE TABLE `player_achievement` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `player_id` int(11) NOT NULL,
              `achievement_id` int(11) NOT NULL,
              `achieved_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_player_achievement` (`player_id`,`achievement_id`),
              KEY `idx_pa_player` (`player_id`),
              KEY `idx_pa_achievement` (`achievement_id`),
              KEY `idx_pa_achieved_at` (`achieved_at`),
              CONSTRAINT `fk_pa_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievement` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_pa_player` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            CREATE TABLE `faction_achievement` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `faction_id` int(11) NOT NULL,
              `achievement_id` int(11) NOT NULL,
              `achieved_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_faction_achievement` (`faction_id`,`achievement_id`),
              KEY `idx_fa_faction` (`faction_id`),
              KEY `idx_fa_achievement` (`achievement_id`),
              KEY `idx_fa_achieved_at` (`achieved_at`),
              CONSTRAINT `fk_fa_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievement` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_fa_faction` FOREIGN KEY (`faction_id`) REFERENCES `faction` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            SQL);
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
            DROP TABLE IF EXISTS `faction_achievement`, `player_achievement`, `faction_season_stat`,
              `player_season_stat`, `achievement`, `card`, `player`, `season`, `faction`, `user`, `role`;
            SQL);
    }
}
