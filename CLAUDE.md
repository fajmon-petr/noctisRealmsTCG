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
- `src/Migrations/` – ruční SQL migrace `YYYY-MM-DD-NN-nazev.sql`
- `tests/` – testy, struktura zrcadlí `src/`
- `plans/` – plány práce

## Pravidla

### Databáze
- **Jakoukoli operaci, která mění databázi (INSERT/UPDATE/DELETE, DDL, spouštění migrací), nejdřív potvrď s uživatelem.** Čtení (SELECT, SHOW) je v pořádku bez ptaní.
- Změny schématu = nový soubor v `src/Migrations/` + úprava entity. Migrace spouští uživatel (nebo Claude po potvrzení).
- Testovací účet (smí zůstat v DB): `claude-test@example.com` / `Admin1` (hráč `ClaudeTest`, frakce Noctis). Používat pro testování stránek po přihlášení.

### Plány
- Plány se ukládají do `plans/` jako `YYYY-MM-DD-nazev.md`.
- Po dokončení se soubor přejmenuje na `YYYY-MM-DD-nazev-done.md`.
- Do plánů psát jen věci podložené kódem; nápady na nové funkce dodává uživatel.

### Testy
- Nette Tester (`vendor/bin/tester`), soubory `*.phpt`.
- `tests/` zrcadlí strukturu `src/NoctisRealmsTCG/`, např. `src/NoctisRealmsTCG/Model/Service/LevelingService.php` → `tests/Model/Service/LevelingService.phpt`.
- Zatím píšeme **unit testy** (bez DB).
- **Integrační testy:** knihovna je připravená (Nette Tester umí i integrační testy). Jakmile Claude vyhodnotí, že jsou potřeba (např. logika závislá na DB dotazech – fasády, statistiky, transakce), navrhne je a začneme je psát. Předtím je potřeba připravit infrastrukturu: testovací DB (např. `noctis_test`), `config/test.neon`, nahrání migrací a základních dat, izolace testů (transakce + rollback). Založení testovací DB podléhá potvrzení uživatele (viz Databáze).
- K nové nebo upravené logice v modelu (služby, fasády, entity) psát testy.

### Kód
- `declare(strict_types=1);`, typované vlastnosti a návratové typy.
- Logika a dotazy patří do fasád/služeb, ne do presenterů. Presentery nepoužívají `EntityManager` přímo.
- Presentery dědí z `BasePresenter` (má `$playerFacade`); stránky jen pro přihlášené z `SecuredPresenter`. Závislosti přes konstruktor (promoted properties).
- Nové fasády/služby registrovat v `config/services.neon` (search prohledává jen `app/`).
- Přístup k entitám: v PHP kódu vždy gettery/settery; v Latte šablonách je povolený property zápis (`$player->faction->slug`) přes `MagicAccessors`.
- Komentáře a texty v UI česky.
- Necommitovat bez vyžádání.

## Příkazy

```sh
php vendor/bin/tester tests -s -C     # testy
composer phpstan                      # statická analýza (level 5)
php vendor/bin/latte-lint src         # kontrola Latte šablon
```
