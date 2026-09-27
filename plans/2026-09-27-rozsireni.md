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

Rarity zůstávají **common, uncommon, rare, epic, legendary** (C/U/R/E/L) – „mythic“ bylo jen pracovní označení pro epic.

### Balíček (nákup)
- Balíček obsahuje **5 karet**, stojí **100 Moon Dustu**.
- Balíčky jsou **podle frakce** (Ignis / Vitae / Noctis) – hráč si vybere, který koupí. Z balíčku padají karty dané frakce + **neutrální karty**.
- Základní drop šance (na kartu): **legendary 1 %, epic 5 %, rare 10 %, uncommon 30 %, common 54 %** (lze doladit).
- **Garantovaný slot („nabíjení“)** podle pořadí koupeného balíčku hráče – v jednom balíčku platí vždy **nejvýš jedno** pravidlo, vyšší má přednost:
  - každý **20.** balíček → 1 karta **legendary** (100 %)
  - jinak každý **10.** balíček → 1 karta **epic nebo vyšší**: epic 90 %, legendary 10 %
  - jinak každý **5.** balíček → 1 karta **rare nebo vyšší**: rare 75 %, epic 20 %, legendary 5 %
  - Příklad: 5. rare+, 10. epic+, 15. rare+, 20. legendary, 25. rare+, 30. epic+, …
  - Balíček s garancí = 1 karta podle garance + 4 karty podle základních šancí; ostatní balíčky = 5 karet podle základních šancí.

### Darování karet frakci
- Hodnota karty: **common 5, uncommon 10, rare 25, epic 50, legendary 100**.
- Za darovanou kartu **hráč dostane tolik Moon Dustu** a **frakce získá stejný počet bodů** (sezónní žebříček).
- Darovat lze jen karty, které má hráč v kolekci **víc než 1×** – poslední kus si vždy nechá.

Implementace: `plans/2026-09-27-balicky.md`

### K rozhodnutí – příjem Moon Dustu (návrh z analýzy ekonomiky, 2026-09-27)
- Dnes je jediný zdroj MD jednorázový bonus 200 MD za výběr frakce (+ darování, až bude hotové) → hráč otevře 2 balíčky a dál nemá z čeho kupovat.
- Návrh: běžný hráč by měl dokončit cyklus 20 balíčků (≈ 1,5 legendary) za **3–4 týdny** → **5–7 balíčků týdně**.
- Možné zdroje: **denní odměna ~50 MD**, **týdenní úkol ~150 MD** (spolu s darováním ~6–7 balíčků týdně).
- Zatím jen návrh – rozhodne autor.
