<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Samostatná tabulka rarit karet.
 * Kód (C/U/R/E/L) zůstává primárním klíčem, takže sloupec `card.rarity` se nemění – jen dostane cizí klíč.
 * Drop šance a hodnota při darování jsou v DB, aby šly ladit bez zásahu do kódu.
 */
final class CreateRarity extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE `rarity` (
              `code` char(1) NOT NULL,
              `name` varchar(32) NOT NULL,
              `sort_order` tinyint(3) unsigned NOT NULL,
              `drop_chance` decimal(5,2) unsigned NOT NULL COMMENT 'základní šance v % na jednu kartu balíčku',
              `donation_value` int(10) unsigned NOT NULL COMMENT 'Moon Dust hráči a body frakci za darovanou kartu',
              PRIMARY KEY (`code`),
              UNIQUE KEY `uq_rarity_sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            INSERT INTO `rarity` (`code`, `name`, `sort_order`, `drop_chance`, `donation_value`) VALUES
              ('C', 'Common',    1, 54.00,   5),
              ('U', 'Uncommon',  2, 30.00,  10),
              ('R', 'Rare',      3, 10.00,  25),
              ('E', 'Epic',      4,  5.00,  50),
              ('L', 'Legendary', 5,  1.00, 100);

            ALTER TABLE `card`
              ADD KEY `fk_card_rarity` (`rarity`),
              ADD CONSTRAINT `fk_card_rarity` FOREIGN KEY (`rarity`) REFERENCES `rarity` (`code`);
            SQL);
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
            ALTER TABLE `card` DROP FOREIGN KEY `fk_card_rarity`, DROP KEY `fk_card_rarity`;
            DROP TABLE `rarity`;
            SQL);
    }
}
