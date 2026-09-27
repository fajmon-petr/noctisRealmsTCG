# Noctis Realms TCG

## O projektu

Webová sběratelská karetní hra (TCG). Hráč se zaregistruje, vybere si jednu ze tří frakcí (**Ignis**, **Vitae**, **Noctis**; v DB je navíc `neutral`) a sbírá **Moon Dust** (herní měna), XP/levely a karty rarit C/U/R/E/L (common, uncommon, rare, epic, legendary). Hráči svými kartami přispívají body své frakci v **sezónách** – frakce i hráči mají sezónní statistiky a žebříčky. Dál jsou založené achievementy (hráčské i frakční), obchod a balíčky (edice „Noctis Alpha“). Veřejná roadmapa je v `HomePresenter::renderDefault()`.

## Stack

- PHP 8.2 (XAMPP), Nette 3.2, Latte 3, Doctrine ORM 3 (nettrine), MySQL/MariaDB (DB `noctis`)
- Frontend: Latte šablony + CSS v `www/assets/css`; Vite je připravený, ale zatím vypnutý
- Lokální adresa: http://localhost/noctisRealmsTCG/www/

## Struktura

- `src/NoctisRealmsTCG/` – namespace `App\` (PSR-4)
  - `Model/Entity` – Doctrine entity (atributy)
  - `Model/Modules/<Modul>` – fasády (`*Facade`)
  - `Model/Service` – doménové služby
  - `Presenters` + `Presenters/templates` – presentery a šablony
- `app/` – Bootstrap, router, chybové presentery (`App\Presentation\Error`)
- `config/` – neon konfigurace (`local.neon` se necommituje)
- `db/migrations/` – Phinx migrace, `db/seeds/` – Phinx seedery (základní data), `db/legacy/` – staré ruční SQL (jen historie)
- `phinx.php` – konfigurace Phinxu; připojení čte z `config/doctrine.neon`
- `tests/` – testy, struktura zrcadlí `src/`
- `plans/` – plány práce

## Pravidla

### Databáze
- **Jakoukoli operaci, která mění databázi (INSERT/UPDATE/DELETE, DDL, spouštění migrací), nejdřív potvrď s uživatelem.** Čtení (SELECT, SHOW) je v pořádku bez ptaní.
- Migrace: **Phinx**. Změna schématu = nová migrace (`php vendor/bin/phinx create NazevZmeny`) + ruční úprava entity. Phinx neumí číst entity – soulad hlídá uživatel, pomocí `php console.php orm:validate-schema`.
- Migrace spouští uživatel (nebo Claude po potvrzení). Pozor: i `phinx status` při prvním připojení k DB vytvoří tabulku `phinxlog` – na DB bez ní je to zápis.
- Prostředí: `development` = DB `noctis`, `testing` = DB `noctis_test`.
- **Výjimka – testy:** integrační testy smí zapisovat do `noctis_test` bez ptaní (každý test se vrací rollbackem). Migrace a jiné zápisy – i do `noctis_test` – dál jen po potvrzení.
- Testovací účet (smí zůstat v DB): `claude-test@example.com` / `Admin1` (hráč `ClaudeTest`, frakce Noctis). Používat pro testování stránek po přihlášení.

### Plány
- Plány se ukládají do `plans/` jako `YYYY-MM-DD-nazev.md`.
- Po dokončení se soubor přejmenuje na `YYYY-MM-DD-nazev-done.md`.
- Do plánů psát jen věci podložené kódem; nápady na nové funkce dodává uživatel.

### Testy
- Nette Tester (`vendor/bin/tester`), soubory `*.phpt`.
- `tests/` zrcadlí strukturu `src/NoctisRealmsTCG/`, např. `src/NoctisRealmsTCG/Model/Service/LevelingService.php` → `tests/Model/Service/LevelingService.phpt`.
- Zatím píšeme **unit testy** (bez DB).
- **Integrační testy:** pro logiku závislou na DB (fasády, statistiky, transakce). Běží proti `noctis_test` (schéma přes Phinx `-e testing`), každý test v transakci s rollbackem. Smí se spouštět bez ptaní (viz Databáze).
  - Třída testu dědí z `Tests\Support\IntegrationTestCase` (`tests/Support/`), metody `test*()`, na konci souboru `(new XxxTest)->run();`
  - K dispozici: `$this->em`, `getService(Třída::class)`, data `createUser()`, `createPlayer(frakce, moonDust)`, `createCard(rarita, frakce)`, `getFaction()`, `getRarity()` – testy si data vytváří samy, na obsah `noctis_test` (kromě seedů) nespoléhají
  - Ochrana: test odmítne běžet proti DB, jejíž název nekončí `_test`; integrační testy běží sériově (zámek)
  - Po nové migraci ji spustit i na `noctis_test` (s potvrzením), jinak testy selžou
- K nové nebo upravené logice v modelu (služby, fasády, entity) psát testy.

### Kód
- `declare(strict_types=1);`, typované vlastnosti a návratové typy.
- Logika a dotazy patří do fasád/služeb, ne do presenterů. Presentery nepoužívají `EntityManager` přímo.
- Presentery dědí z `BasePresenter` (má `$playerFacade`); stránky jen pro přihlášené z `SecuredPresenter`. Závislosti přes konstruktor (promoted properties).
- Nové fasády/služby registrovat v `config/services.neon` (search prohledává jen `app/`).
- Chyby pro hráče: výjimka odvozená z `App\Model\UserException` (česká zpráva → presenter ji ukáže jako flash). Fasáda ji vyhazuje jen **před** změnou dat.
- Zápisové operace fasád v transakci přes trait `App\Model\Database\ManualTransaction` (ne `wrapInTransaction` – ten při výjimce zavře EntityManager). Souběh: zamknout řádek (`refresh(..., LockMode::PESSIMISTIC_WRITE)`), sčítání statistik atomicky v SQL.
- Přístup k entitám: v PHP kódu vždy gettery/settery; v Latte šablonách je povolený property zápis (`$player->faction->slug`) přes `MagicAccessors`.
- Entity s `MagicAccessors`: Doctrine při inicializaci lazy proxy zapisuje vlastnosti přes `__set`. `MagicAccessors::__set` proto deklarované vlastnosti zapisuje přímo (bez setteru). Magický zápis `$entity->prop = …` v kódu nepoužívat – vždy settery. Při změně `MagicAccessors` spustit test `PackFacade::testOtevrenyBalicekJdeZnovuNacist` (načtení proxy).
- Mapování entit drž v souladu s DB včetně názvů indexů (`#[ORM\Index]`, `#[ORM\UniqueConstraint]` jako samostatné atributy – ne uvnitř `#[ORM\Table]`). Kontrola: `php console.php orm:validate-schema`.
- Komentáře a texty v UI česky.

### Git
- **Nikdy necommitovat** (ani `git add`, `git stash`, `git reset` a jiné operace měnící stav repozitáře). Uživatel si změny vždy nejdřív sám zkontroluje a commituje sám. Po dokončení práce jen shrnout, co se změnilo.

## Příkazy

```sh
php vendor/bin/tester tests -s -C     # testy
composer phpstan                      # statická analýza (level 5)
php vendor/bin/latte-lint src         # kontrola Latte šablon
php console.php orm:validate-schema   # soulad entit s DB (jen čte)

# migrace (mění DB – jen po potvrzení)
php vendor/bin/phinx status [-e testing]
php vendor/bin/phinx migrate [-e testing]
php vendor/bin/phinx seed:run -e testing
```
