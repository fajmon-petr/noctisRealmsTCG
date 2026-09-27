Noctis Realms TCG
=================

Webová sběratelská karetní hra. Hráč si vybere jednu ze tří frakcí (Ignis, Vitae, Noctis),
sbírá Moon Dust, XP a karty (rarity C/U/R/E/L) a svými kartami přispívá body frakci
v sezónních žebříčcích.

Postaveno na Nette 3.2, Latte 3, Doctrine ORM 3 a MySQL/MariaDB.


Požadavky
---------

- PHP 8.2+ (rozšíření `pdo_mysql`)
- MySQL / MariaDB
- Composer
- Apache s `mod_rewrite` (např. XAMPP), případně vestavěný PHP server


Instalace
---------

1. Závislosti:

		composer install

2. Připojení k databázi je v `config/doctrine.neon` (sekce `doctrine.dbal`).
   Stejné nastavení používají i migrace (`phinx.php`).

3. Vytvoř databázi (výchozí název `noctis`, kódování `utf8mb4`) a nahraj schéma a základní data:

		php vendor/bin/phinx migrate
		php vendor/bin/phinx seed:run

4. Adresáře `temp/` a `log/` musí být zapisovatelné.


Spuštění
--------

- XAMPP: projekt v `htdocs/noctisRealmsTCG` → http://localhost/noctisRealmsTCG/www/
- Vestavěný server:

		php -S localhost:8000 -t www

Veřejně dostupná má být jen složka `www/` (kořenový `.htaccess` zbytek blokuje).


Vývoj
-----

	php vendor/bin/tester tests -s -C     # testy (Nette Tester)
	composer phpstan                      # statická analýza
	php vendor/bin/latte-lint src         # kontrola Latte šablon
	php console.php orm:validate-schema   # soulad entit s databází

Migrace (Phinx) jsou v `db/migrations`, seedery v `db/seeds`. Nová migrace:

	php vendor/bin/phinx create NazevZmeny

Prostředí `testing` pracuje s databází `noctis_test` (`-e testing`).

Pravidla projektu, struktura a konvence jsou popsané v `CLAUDE.md`, plány práce ve složce `plans/`.
