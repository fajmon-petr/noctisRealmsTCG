# Prvotní refaktor kódu

Datum: 2026-09-27

## Fáze A – opravy rozbitých věcí

| # | Stav | Soubor | Úprava |
|---|---|---|---|
| A1 | ✅ | `Model/Modules/Player/SeasonStatsFacade.php` | `$this->db` → `$this->connection` v `addContribution()` |
| A2 | ✅ | `Presenters/ShopPresenter.php` + `templates/Shop/*` | Namespace `App\Model\Entity\Card`; řazení jen `new` (id) / `name_asc`; filtr frakce přes join na `f.slug`; `Paginator`; šablony bez `method_exists`/ceny, oprava `{var}` syntaxe |
| A3 | ✅ | `Model/Entity/Faction.php` | `getEmblem()`/`setEmblem()` pracují s `$emblem` |
| A4 | ✅ | `Presenters/PlayerPresenter.php` | `getOrCreateForUser`; sezóna = `?season` ?: otevřená ?: první; null-safe `$current`; fallback slugu frakce |
| A5 | ➡️ | `PlayerPresenter::composeAchievementsTab()` | Není chyba – šablona zobrazuje počet (int) správně, seznam je označen jako WIP. Přesunuto do rozšíření (bod 4). |
| A6 | ✅ | `src/Migrations/2025-10-22-02-cardEntity.sql`, `2025-10-23-01-renameDbTables.sql` | FK constraint uvnitř `CREATE TABLE`; `CHANGE profile_id player_id` (bez `TO`). Ověřeno na dočasné DB. |
| A7 | ✅ | `config/common.neon` | Mapping `Error: App\Presentation\Error\**Presenter` |
| A8 | ➖ | `PlayerPresenter.php:147` | Nepotřeba – šablona už skládá `{$basePath}{$avatarPath}` |

Ověřeno (2026-09-27): `/`, `/shop/` (+ filtry), `/shop/detail/1`, `/faction/`, `/player/` pro nového hráče bez frakce i s frakcí, `?season=1/2`.

## Fáze B – úklid struktury

- ✅ B1: `SecuredPresenter` s kontrolou přihlášení ve `startup()`; `PlayerPresenter` z něj dědí a načítá `$player` jednou ve `startup()`
- ✅ B2: Presentery bez `EntityManager`; závislosti přes promoted constructor properties
- ✅ B3: `FactionPresenter` bez `method_exists()` magie, parametry `slug`/`season` přes signaturu. Navíc opraveno: šablona četla `$seasonStats`/`$achievementsUpcoming`, které presenter neposílal (body vždy 0, achievementy prázdné); `factionCards` z reálných statistik místo natvrdo; `belongsToFaction` podle frakce hráče; `FactionFacade::getSeasonStats()` volala neexistující gettery `getCardsCommon()` atd. Level frakce a cíle příspěvků zůstávají jako označené TODO placeholdery.
- ✅ B4: Moon Dust v `BasePresenter` přes `PlayerFacade::findByUserId()`
- ✅ B5: Nové/rozšířené fasády – `PlayerFacade` (`findByUserId`, `getForUserId`, `saveProfile` s bonusem v transakci), `SeasonFacade` (`getById`, `getCurrentSeason`), `CardFacade` (`search`, `getById`)
- ✅ B6: Konvence zapsaná v `CLAUDE.md` – v PHP gettery, v šablonách property zápis
- ✅ B7: `getFactionBySlugOrDefault()` – slug, jinak `DEFAULT_SLUG = 'ignis'`
- ✅ B8: Smazán `app/Presentation/Home/*` (+ `composer dump-autoload`); `app/Presentation/@layout.latte` zůstává (používají ho chybové stránky); `Pack.php`, `FormFactory.php` ponechány
- ✅ B9: Migrace → **Phinx** (rozhodnutí uživatele 2026-09-27)
  - ✅ `nettrine/migrations` odebrán, `robmorgan/phinx ^0.16` přidán (Composer zároveň povýšil Symfony 7.3 → 7.4); `allow-plugins.phpstan/extension-installer: false`
  - ✅ `phinx.php` – připojení z `config/doctrine.neon`, prostředí `development` (`noctis`) a `testing` (`noctis_test`)
  - ✅ `db/migrations/20260927000000_initial_schema.php` – baseline = přesná kopie aktuálního schématu
  - ✅ `db/seeds/` – Role, Faction, Season, Achievement (data z aktuální DB)
  - ✅ Staré SQL přesunuty do `db/legacy/`
  - ✅ Oprava konzole: `console.php` přes `Bootstrap::bootConsoleApplication()`, `ConsoleExtension(%consoleMode%)`, odstraněná duplicitní deklarace – funguje `orm:validate-schema`
  - ✅ Ověřeno na nové DB `noctis_test` (s potvrzením): baseline + seedy → schéma identické s `noctis`, data rolí/frakcí/sezón/achievementů shodná. `noctis_test` zůstává pro integrační testy.
  - ✅ Baseline v `noctis` označena jako provedená (`phinx migrate --fake`, s potvrzením) – `phinx status`: `up`, schéma i data beze změny
  - Pozn.: `phinx status` omylem vytvořil prázdnou tabulku `phinxlog` v `noctis`
- ✅ B10: **Soulad entit s DB** – upraveny **jen entity podle DB** (DB beze změny, obsahuje reálná data):
  - `Achievement::$type` nullable (`?string`, výchozí `null` místo `'created'`) – dřív by načtení 4 z 5 achievementů spadlo
  - `FactionSeasonStats::$season` povinná (`NOT NULL`, `CASCADE` jako v DB); odebrán nepoužitý `setLastUpdateAt()`
  - `Faction` – doplněny `banner`, `perk`, `selectable` (`is_selectable`) + gettery/settery
  - `PlayerSeasonStats` – odstraněno dvojí mapování `season_id` (vztah + `int`), `getSeasonId(): ?int` přes vztah; doplněn `last_update_at` (plní DB)
  - Sjednocené `unsigned`, délky (`avatar` 100, `emblem` varchar 255), výchozí hodnoty, `TEXT` a **názvy indexů** (samostatné `#[ORM\Index]`/`#[ORM\UniqueConstraint]` – Doctrine 3 ignoruje `uniqueConstraints:` v `#[ORM\Table]`)
  - `IgnorePhinxlogFilter` – Doctrine ignoruje tabulku `phinxlog`
  - **Nalezená a opravená chyba:** `Player::setAvatar(string)` → `?string`. Při inicializaci Doctrine proxy (např. hráč načtený přes statistiky) šla hydratace přes `MagicAccessors::__set` a hráč bez avataru shodil stránku. Pravidlo zapsáno do `CLAUDE.md`.
  - Ověřeno: všechny entity se načtou v libovolném pořadí vč. proxy, PHPStan bez chyb, testy OK, stránky anonymně i přihlášeně 200
  - Zbývá (rozhodnutí uživatele): 2 cizí klíče s `ON UPDATE CASCADE` (`player_season_stat.season_id`, `user.role_id`) – v mapování Doctrine nejdou vyjádřit, `orm:validate-schema` je proto hlásí. Srovnat by šlo jen migrací v DB; prakticky nevadí (ID se nemění).

Poznámka: `Model/Modules/Faction/FactionStatsFacade.php` se nikde nepoužívá a pracuje se starými názvy tabulek/sloupců (`faction_season_stats`, `factions`, `cards_common`…). Patří k rozšíření (body 5–8) – opravit, až se bude používat.

Ověřeno (2026-09-27): všechny stránky anonymně i přihlášeně (`claude-test`), uložení profilu bez opakovaného bonusu, PHPStan na změněných souborech bez chyb.

## Fáze C – kvalita

- ✅ C1: `composer phpstan` (level 5) na celém projektu – **bez chyb**
  - Zapnuto `phpstan-doctrine` (extension + rules) – odstranilo falešné nálezy u entit a přidalo kontrolu mapování
  - Oprava: `PlayerSeasonStats::setFaction()` zapisovalo do `$this->setFaction` (→ `LogicException` z `MagicAccessors`)
  - Oprava: mapování `PlayerSeasonStats::$finalRank` doplněno o `nullable: true` (odpovídá DB i typu vlastnosti – řeší jednu položku B10)
  - `User::$role` jako `Role` (ne nullable) – v DB je povinná
  - `FactionStatsFacade` – odebrán nepoužívaný `$em`
- ✅ C2: `tests/Model/Service/LevelingService.phpt` – 8 testů (křivka, level pod 1, přenos XP, víc levelů naráz, záporný zisk, procenta). Ověřeno, že test při chybné hodnotě selže.
- ✅ C3: Vlastní `readme.md` (popis, požadavky, instalace s Phinxem, spuštění, vývojové příkazy)

Poznámka: `config/local.neon` se v `Bootstrap` nenačítá a obsahuje nepoužívané parametry (`dbname: test`). Připojení k DB je jen v `config/doctrine.neon`.
