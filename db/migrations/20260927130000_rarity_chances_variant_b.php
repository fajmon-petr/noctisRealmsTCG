<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Drop šance podle varianty B (cíl ~1,5 legendary / ~3 epic / ~6 rare na 20 balíčků).
 * Šance garantovaných slotů jsou v kódu: PackRules::GUARANTEE_CHANCES.
 * Navíc `sort_order` na SMALLINT – Doctrine (DBAL 4) TINYINT nezná.
 */
final class RarityChancesVariantB extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            ALTER TABLE `rarity` MODIFY `sort_order` smallint(5) unsigned NOT NULL;

            UPDATE `rarity` SET `drop_chance` = 68.00 WHERE `code` = 'C';
            UPDATE `rarity` SET `drop_chance` = 25.00 WHERE `code` = 'U';
            UPDATE `rarity` SET `drop_chance` =  4.50 WHERE `code` = 'R';
            UPDATE `rarity` SET `drop_chance` =  2.00 WHERE `code` = 'E';
            UPDATE `rarity` SET `drop_chance` =  0.50 WHERE `code` = 'L';
            SQL);
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
            UPDATE `rarity` SET `drop_chance` = 54.00 WHERE `code` = 'C';
            UPDATE `rarity` SET `drop_chance` = 30.00 WHERE `code` = 'U';
            UPDATE `rarity` SET `drop_chance` = 10.00 WHERE `code` = 'R';
            UPDATE `rarity` SET `drop_chance` =  5.00 WHERE `code` = 'E';
            UPDATE `rarity` SET `drop_chance` =  1.00 WHERE `code` = 'L';

            ALTER TABLE `rarity` MODIFY `sort_order` tinyint(3) unsigned NOT NULL;
            SQL);
    }
}
