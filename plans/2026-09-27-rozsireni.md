# Návrh rozšíření

Datum: 2026-09-27

Jen věci, které už jsou v kódu založené (roadmapa v `HomePresenter::renderDefault()`, prázdné soubory, placeholdery). Vlastní myšlenky se doplní.

1. **Balíčky / edice „Noctis Alpha“** (roadmapa: in-progress) – prázdné `Model/Entity/Pack.php` a `Model/Modules/Card/CardFacade.php`; rarity a drop šance. Rarity C/U/R/E/L existují na `Card` i ve statistikách, `Player` má `cards` a `openedPacks`.
2. **Otevírání balíčků** (roadmapa: todo) – React komponenta + API endpoint, animace. Připravený `SeasonStatsFacade::addContribution()` pro body do sezóny.
3. **Obchod** (roadmapa: todo) – `ShopPresenter` s filtry/stránkováním, `Shop/detail.latte` téměř prázdná; nákup Moon Dust balíčků (cena, potvrzení, odečet MD).
4. **Achievementy** – tabulky `achievement`, `player_achievement`, `faction_achievement`; naseedované `OPEN_1_PACK`, `OPEN_10_PACKS`, `WIN_1_MATCH`, `WIN_10_MATCH`, `DONATE_DUST`. Chybí logika udělování; `WIN_*` předpokládá zápasy, které v kódu nejsou.
5. **Přispívání Dustu frakci** – `FactionPresenter::actionAddDust()` je stub; šablona počítá s `dustGoal`/`dustProgress`, `cardsGoal`/`cardsProgress` (natvrdo).
6. **Stránka frakce** – level frakce (`xp`, `xpToNext`, `nextLevel` natvrdo), `belongsToFaction` natvrdo, `factionCards` natvrdo, zakomentované `getLastContributions()`, `rank` placeholder; `Faction` má `playersCount` a `factionPoints`. Roadmapa: žebříček hráčů frakce, počet hráčů.
7. **Hlavní přehled frakcí** (roadmapa: todo) – žebříčky, body, počet karet, koeficient, vítězné body.
8. **Uzavírání sezón** – `finalRank` na `PlayerSeasonStats` i `FactionSeasonStats`, zatím nenastavován; živé pořadí přes `getLiveRank()`.
9. **Vlastní avatary** – `Player::$avatar` a cesta `/uploads/avatars/`; upload chybí.
10. **Darování karet** (roadmapa: idea) – poslání karty jinému hráči, poplatek v MD.
11. **Vite + React** (roadmapa: todo) – `vite.config.ts`, `package.json` připravené, v `common.neon` zakomentované `type: vite`.
12. **Zdroj XP** – `LevelingService::addXp()` existuje, nikde se nevolá.
13. **Neutrální frakce** – migrace `neutralFaction`, vyřazená v `getMainFactions()`; účel k doplnění.

## Myšlenky autora

_(doplní se)_
