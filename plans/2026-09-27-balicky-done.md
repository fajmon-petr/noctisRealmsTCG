# Balíčky, batoh, kolekce a darování karet

Datum: 2026-09-27

Pravidla viz `plans/2026-09-27-rozsireni.md` → „Myšlenky autora“. Tento plán je technický návrh implementace.

## Rozhodnutí uživatele (2026-09-27)

- **Batoh:** koupený balíček jde do batohu hráče. Hráč může nakoupit víc balíčků a otevírat je kdykoli později.
- **Garance** se počítá podle počtu **otevřených** balíčků hráče (jedno počítadlo pro všechny frakce a sezóny, `Player::$openedPacks`, zobrazené u hráče). Rozvrh pokračuje dál: 5. rare+, 10. epic+, 15. rare+, 20. legendary, 25. rare+, 30. epic+, 35. rare+, 40. legendary, …
- Garance se **neresetuje**, když vysoká rarita padne náhodou.
- **Body frakci jen za darování**, otevření balíčku body nedává.
- Darovat lze **jen do vlastní frakce** hráče (kontrola na serveru, ne jen v šabloně) a do **probíhající sezóny**.
- **Rarity mají vlastní tabulku v DB** (šance a hodnota darování laditelné v DB).
- Animace otevírání v **Reactu** – později (Vite + React z roadmapy); teď jednoduché zobrazení výsledku v Latte.

## Šance – varianta B (rozhodnutí 2026-09-27)

Analýza (agent, simulace 100 000 hráčů): původní šance 54/30/10/5/1 dávaly na 20 balíčků ~2,2 legendary / 6,1 epic / 11,1 rare – příliš štědré. Cíl autora ~1,5 L / ~3 E / ~6 R.

| | C | U | R | E | L |
|---|---|---|---|---|---|
| Základní šance (DB `rarity`) | 68 % | 25 % | 4,5 % | 2 % | 0,5 % |
| Garance rare+ (5., 15., 25. …) | – | – | 90 % | 10 % | – |
| Garance epic+ (10., 30., 50. …) | – | – | – | 100 % | – |
| Garance legendary (20., 40. …) | – | – | – | – | 100 % |
| **Průměr na 20 balíčků** | | | **6,1** | **3,1** | **1,5** |

- Darovací hodnota balíčku (vše darováno) ~51 MD při ceně 100 → nekonečná smyčka „daruj → kup“ nevzniká. Doporučení: dlouhodobě držet darování ≤ 50 % ceny balíčku.
- Až bude víc karet, přepočítat podíl duplikátů podle skutečné velikosti poolu (simulační skript ve scratchpadu agenta: `economy/sim.php`).

## Zjištěný stav (2026-09-27)

- Tabulka rarit v DB neexistovala – rarita byla jen `card.rarity` CHAR(1) bez vazby.
- Karty v DB: jen 4 testovací – C (ignis), C (neutral), U (vitae), R (noctis). **Epic a legendary karty chybí úplně**, žádná frakce nemá všechny rarity.
- Ochrana darování do cizí frakce v kódu **není** – jen `belongsToFaction` skrývá formulář v šabloně; `FactionPresenter::actionAddDust()` nic nekontroluje ani neukládá.
- Pokud v poolu (frakce + neutrální) chybí karta vylosované rarity, vezme se nejbližší nižší rarita (u garance nejbližší vyšší, pokud existuje). Pro skutečnou hru je potřeba doplnit karty všech rarit.

## Datový model

### Migrace (Phinx)
1. ✅ `20260927120000_create_rarity.php`: tabulka `rarity` (`code` PK C/U/R/E/L, `name`, `sort_order`, `drop_chance` %, `donation_value`) + data + FK `card.rarity → rarity.code`
2. `player_card` – kolekce: `player_id`, `card_id`, `quantity` (≥ 1), `obtained_at`, UNIQUE (`player_id`, `card_id`)
3. `player_pack` – batoh: `player_id`, `faction_id` (frakce balíčku), `purchased_at`, `opened_at` (NULL = neotevřený), `open_number` (pořadí otevření – pro garance a historii), `guarantee` (NULL / R / E / L)
4. `player_pack_card` – obsah otevřeného balíčku: `player_pack_id`, `card_id`, `slot` (1–5), `guaranteed` (bool) – výsledek po přesměrování, historie, později data pro React animaci

Spuštění migrací na `noctis` / `noctis_test` jen s potvrzením.

### Kód
- `Model/Entity/Rarity.php` – entita nad tabulkou `rarity`; `Card::$rarity` → vztah na `Rarity` (šablony: `$card->rarity->name`).
- `Model/Entity/PlayerCard.php`, `PlayerPack.php`, `PlayerPackCard.php`.
- `Model/Entity/Pack.php` (dnes prázdný) – **smazat**.
- `Model/Service/Pack/PackRules.php` – cena 100, 5 karet, rozvrh garancí 5/10/20 a šance garantovaných slotů (rare+: 75/20/5, epic+: 90/10, legendary: 100). Základní šance se berou z tabulky `rarity`.
- `Model/Service/Pack/PackGenerator.php` – čistá logika: `(pořadí otevření, základní šance, Randomizer) → 5 rarit` (1 garantovaná + 4 základní). `Random\Randomizer` (PHP 8.2) → v testech se seedem deterministické.
- `Model/Modules/Pack/PackFacade.php`
  - `buy(Player, Faction, počet)` – transakce se zámkem hráče (`PESSIMISTIC_WRITE`): kontrola MD ≥ 100 × počet a volitelné frakce (ne neutrální), odečet MD, vložení balíčků do batohu
  - `open(Player, PlayerPack)` – transakce se zámkem: balíček patří hráči a je neotevřený, `openedPacks++` → pořadí → `PackGenerator` → náhodné karty z poolu frakce balíčku + neutrální → `player_pack_card`, kolekce (`quantity++`), `Player::$cards`
  - `getBackpack(Player)` – neotevřené balíčky podle frakce
- `Model/Modules/Card/CollectionFacade.php` – kolekce hráče (seznam s počty), `donate(Player, Card, počet)` v transakci se zámkem:
  1. hráč má frakci; darování jde vždy do **jeho** frakce (frakce se nebere z požadavku)
  2. existuje probíhající sezóna
  3. `quantity - počet ≥ 1`
  4. odečet kusů, hráči `+donation_value × počet` MD
  5. body + počet karet dané rarity do `player_season_stat` (hráč) a `faction_season_stat` (frakce)
- Oprava `FactionStatsFacade` (staré názvy tabulek `faction_season_stats`/`factions`, sloupců `cards_*`).
- `PlayerSeasonStats::addCard()` (prázdné TODO) – smazat, nahrazeno fasádou.
- `FactionPresenter::actionAddDust()` (stub bez kontroly) – zatím odstranit/zakázat, darování Dustu není součástí tohoto plánu.

### UI (Latte, React později)
- **Obchod** (`Shop:default`): 3 balíčky (Ignis/Vitae/Noctis), cena, stav MD, formulář Koupit s počtem (CSRF). Katalog karet zůstává pod tím.
- **Batoh** (profil nebo `Player:backpack`): neotevřené balíčky podle frakce, tlačítko Otevřít; počet otevřených balíčků a „do další garance zbývá X (rare+ / epic+ / legendary)“.
- **Výsledek otevření** (`Player:pack/<id>`): 5 karet (obrázek, název, rarita, zvýrazněná garantovaná) – čte z `player_pack_card` (PRG).
- **Kolekce** (profil, sekce „Úspěchy & Karty“ – dnes „Seznam (WIP)“): mřížka karet s počtem kusů, filtr; u karet s počtem > 1 formulář „Darovat“ (počet max. `quantity - 1`) s hodnotou v MD/bodech.
- Stránka frakce – blok „Přispět karty“ odkáže do kolekce (jen pro členy frakce).

## Testy

- **Unit:** `PackGenerator` – rozvrh garancí (5./10./15./20./25./40. …, nikdy víc garancí najednou), vždy 5 karet, garantovaná karta splňuje minimum, statistické rozložení se seedem (tolerance ±0,5 p. b.).
- **Integrační (poprvé):**
  - `config/test.neon` → DB `noctis_test`, bootstrap s kontejnerem, každý test v transakci + rollback
  - `PackFacade`: nedostatek MD, nákup více balíčků, otevření cizího / už otevřeného balíčku, počítadlo a garance
  - `CollectionFacade`: zákaz darování posledního kusu, darování jen do vlastní frakce, připsání MD a bodů hráči i frakci

## Pořadí prací

1. ✅ Migrace `rarity` – spuštěna na `noctis` i `noctis_test` (s potvrzením); entita `Rarity` (read-only číselník + konstanty kódů), `Card::$rarity` je vztah na `Rarity` (EAGER); `CardFacade::getRarities()`, filtr a výpis v obchodě podle tabulky
   - Pozn.: `rarity.sort_order` je v DB `TINYINT`, Doctrine (DBAL 4) zná nejmenší `SMALLINT` → `orm:validate-schema` hlásí 1 kosmetický rozdíl. Opravit `ALTER … SMALLINT` v další migraci (krok 3).
2. ✅ `PackRules` + `PackGenerator` + unit testy
   - `Model/Service/Pack/PackRules.php` – cena, počet karet, rozvrh garancí (`guaranteeFor()`), šance garantovaných slotů, `packsUntilGuarantee()` pro UI „do garance zbývá X“
   - `Model/Service/Pack/PackGenerator.php` – `generate(pořadí, šance)` → 5× `PackSlot` (garantovaný slot je poslední), `pick()` vážený výběr na celých číslech, šance se berou poměrově; registrován v `services.neon`
   - `Model/Service/Pack/PackSlot.php` – readonly (rarita, guaranteed)
   - Testy: `tests/Model/Service/Pack/PackRules.phpt` (rozvrh 1–100, počty na 40 balíčků, zbývající balíčky), `PackGenerator.phpt` (5 karet, garance jen 1× a poslední, minimum garance, determinismus seedu, rozložení 100 000 losů ±0,5 p. b., garance rare+ ±1 p. b., neplatné šance). Ověřeno, že testy chytí prohozené pořadí garancí i off-by-one ve výběru.
3. ✅ Migrace + entity – spuštěno na `noctis` i `noctis_test` (s potvrzením)
   - `20260927130000_rarity_chances_variant_b.php` – drop šance 68 / 25 / 4,5 / 2 / 0,5 %, `sort_order` → SMALLINT
   - `20260927130100_create_collection_and_backpack.php` – `player_card`, `player_pack` (UNIQUE `player_id`+`open_number` = pořadí otevření nejde zdvojit), `player_pack_card`
   - Entity `PlayerCard` (`add()`, `remove()` nedovolí odebrat poslední kus, `getDonatableQuantity()`), `PlayerPack` (`open()`, `addCard()`, `isOpened()`), `PlayerPackCard`
   - `PackRules::GUARANTEE_CHANCES` podle varianty B: rare+ = R 90 / E 10, epic+ = E 100, legendary = L 100; testy upraveny
   - `orm:validate-schema`: zbývají jen 2 známé rozdíly `ON UPDATE CASCADE` (viz refaktor B10)
4. ✅ Infrastruktura integračních testů
   - `config/test.neon` (DB `noctis_test`), `Bootstrap::bootTestContainer()`
   - `tests/Support/IntegrationTestCase.php` – čerstvý kontejner na test, transakce + rollback, sériový běh (zámek), ochrana proti DB bez `_test`, pomocné metody pro data
   - `composer.json` → `autoload-dev` pro `Tests\Support\`
   - Testy: `tests/Support/IntegrationTestCase.phpt` (správná DB, rollback opravdu maže, pomocné metody), `tests/Model/Modules/Player/PlayerFacade.phpt` (7 testů – profil jen jednou, bonus jen při první volbě, neplatná frakce nic nezmění, uložení do DB). Ověřeno mutací, že test chytí chybu; `noctis_test` po testech prázdná.
   - Návod v `CLAUDE.md` → Testy
5. ✅ `PackFacade` (nákup do batohu, otevření) + integrační testy
   - `Model/Modules/Pack/PackFacade.php`: `buy()` (1–10 balíčků, jen volitelná frakce, zámek hráče + kontrola MD), `open()` (vlastník, neotevřený, neprázdný pool; pořadí otevření → garance → karty z frakce + neutrální → `player_pack_card` + kolekce), `getBackpack()`, `getPlayerPack()`, `getBaseChances()`
   - Chybějící rarita v poolu: běžný slot → nižší, garantovaný → nejdřív vyšší
   - `PackException` – chyby pro hráče (česky); všechny kontroly **před** změnou dat, transakce řízená ručně (ne `wrapInTransaction`, ten by při výjimce zavřel EntityManager a presenter by nevykreslil hlášku)
   - `Faction::NEUTRAL_SLUG`, `PackRules::MAX_BUY_AT_ONCE = 10`, `PackGenerator::pickRandom()`
   - Testy: `tests/Model/Modules/Pack/PackFacade.phpt` – 18 testů (nákup, uložení, nedostatek MD bez změn a s použitelným EM, neutrální frakce, počty; otevření 5 karet do kolekce, stejná karta = víc kusů, cizí / dvakrát otevřený / prázdný pool beze změn, pool jen frakce + neutrální, garance 5./10./20., náhrada chybějící rarity, pořadí napříč frakcemi). Mutace (kontrola vlastníka, počítadlo, kontrola MD) testy chytí.
   - Pozn. k nasazení: mimo debug režim Doctrine negeneruje proxy třídy automaticky → na produkci po nasazení `php console.php orm:generate-proxies`. Testovací kontejner běží v debug režimu.
5b. ✅ UI balíčků (předsunuto z kroku 7, aby šlo zkoušet v prohlížeči)
   - Obchod: sekce „Balíčky“ (3 frakce, počet 1–10, Koupit → batoh); `ShopPresenter::createComponentBuyPackForm()` (Multiplier podle frakce, CSRF)
   - Batoh `Player:backpack`: neotevřené balíčky podle frakce, Otevřít (nejstarší balíček frakce), počet otevřených, další garance, „do garance zbývá“
   - Výsledek `Player:pack/<id>`: 5 karet s barvou rarity, štítek „Garance“; cizí/neotevřený balíček → 404
   - Menu: odkaz „Batoh“; `FactionFacade::getBySlug()`
   - Testovací data v `noctis` (s potvrzením): `db/seeds/TestCardSeeder.php` (chybějící rarity pro každou frakci → 20 karet, na `*_test` DB se přeskočí), `claude-test` 5 000 MD
   - Ověřeno bez zápisu do DB: stránky se vykreslí, neplatný počet ukáže chybu. Nákup/otevření zkouší uživatel.
   - Otevírání více balíčků (přání uživatele): `PackFacade::openMany(hráč, frakce|null, počet)` (max. `PackRules::MAX_OPEN_AT_ONCE = 50`, každý balíček ve vlastní transakci), `getBackpackSummary()`; batoh: u frakce počet + „Otevřít“ + „Otevřít vše (N)“, nahoře „Všechny balíčky“; výsledek `Player:opened?ids=1-2-3` (souhrn rarit, všechny balíčky, „Otevřít další“ pro stejnou frakci, kolik zbývá v batohu, garance); `Player:pack/<id>` přesměruje na nový výsledek. Sdílené bloky `templates/Player/@packs.latte`. Testy `openMany` (4).
   - 🐛 Nalezeno uživatelem: stránka výsledku padala (`Property 'name' not writable on …Proxy…\Rarity`). Příčina: Doctrine při inicializaci lazy proxy zapisuje vlastnosti přes `__set` a `MagicAccessors` to posílal do setterů (`Rarity` žádné nemá). Oprava v `MagicAccessors::__set` – deklarované vlastnosti se zapisují přímo. Regresní test `testOtevrenyBalicekJdeZnovuNacist`. Tím je vyřešena i dřívější třída chyb (viz `setAvatar` v refaktoru B10).
6. ✅ `CollectionFacade` (darování) + oprava `FactionStatsFacade` + integrační testy
   - `Model/Modules/Card/CollectionFacade.php`: `getCollection()` (od nejvzácnějších), `donate(hráč, karta, počet)` → `DonationResult` (karta, počet, frakce, MD, body). Kontroly před změnou dat: počet ≥ 1, hráč má frakci, probíhá sezóna, karta je v kolekci, zůstane ≥ 1 kus. Frakce je **vždy frakce hráče** (nebere se z požadavku). Zámek hráče i řádku kolekce.
   - Body: `SeasonStatsFacade::addContribution()` (hráč) a `FactionStatsFacade::addContribution()` (frakce) – atomický `INSERT … ON DUPLICATE KEY UPDATE`; obě metody opraveny (neexistující sloupce `cards_*`, staré tabulky `faction_season_stats`/`factions`), sezóna povinná (`faction_season_stat.season_id` je NOT NULL). Z `FactionStatsFacade` odstraněna nepoužívaná duplicitní `getSeasonStatsView()`, `getLeaderboard()`/`setFinalRank()` opraveny (bez testů – použijí se u uzavírání sezón).
   - Společné: `App\Model\UserException` (základ `PackException`, `DonationException`), trait `App\Model\Database\ManualTransaction` (vytaženo z `PackFacade`).
   - Testy: `tests/Model/Modules/Card/CollectionFacade.phpt` – 12 testů (MD a kusy, body hráči i frakci podle rarity, sčítání opakovaných darů, poslední kus, víc než duplikáty, karta mimo kolekci / cizí, neplatný počet, hráč bez frakce, body vždy vlastní frakci, bez sezóny, řazení kolekce). Mutace (frakce karty místo hráče, poslední kus, frakce bez bodů, bez MD) testy chytí.
   - UI (6b): `Player:collection` – kolekce s filtrem rarity, počet kusů, formulář Darovat (max. duplikáty), info o frakci/sezóně a hodnotách; menu „Kolekce“; na stránce frakce „Darovat karty z kolekce“ místo zakomentovaného formuláře.
7. ✅ UI: obchod, batoh, výsledek otevření, kolekce s darováním (většina v 5b a 6), doplněn profil: akce Kolekce / Batoh (počet neotevřených) / Obchod, sekce „Karty“ s odkazy místo „Seznam (WIP)“, odznaky zůstávají WIP
8. ✅ Úklid: smazány prázdné `Model/Entity/Pack.php` a `Model/Modules/Player/FormFactory.php`, `PlayerSeasonStats::addCard()` (prázdné TODO), `FactionPresenter::actionAddDust()` (stub bez kontroly a ukládání) + formulář „Přispět Dust“ na stránce frakce; kódy rarit `'C'…'L'` v PHP nahrazeny konstantami `Rarity::*` (`FactionFacade`, `FactionPresenter`, `SeasonStatsFacade`, `FactionStatsFacade`)

## Stav k uzavření (2026-09-27)

- Hotovo: rarity v DB, balíčky (nákup do batohu, otevírání jednotlivě i hromadně, garance), kolekce, darování do vlastní frakce, UI pro všechno, unit + integrační testy (7 souborů), PHPStan bez chyb.
- Zůstává mimo tento plán: grafika a animace (React), obrázky skutečných karet (testovací karty z `TestCardSeeder`), placeholdery na stránce frakce (level frakce, cíle Dust/karet), login bonus, uzavírání sezón (`FactionStatsFacade::getLeaderboard()`/`setFinalRank()` bez testů), úprava darování podle nové myšlenky autora.
- `orm:validate-schema`: jen 2 známé rozdíly `ON UPDATE CASCADE`.
