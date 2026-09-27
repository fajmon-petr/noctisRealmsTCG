# Návrh rozšíření

Datum: 2026-09-27

Jen věci, které už jsou v kódu založené (roadmapa v `HomePresenter::renderDefault()`, prázdné soubory, placeholdery). Vlastní myšlenky se doplní.

1. ✅ **Balíčky / edice „Noctis Alpha“** (hotovo – `plans/2026-09-27-balicky-done.md`) (roadmapa: in-progress) – prázdné `Model/Entity/Pack.php` a `Model/Modules/Card/CardFacade.php`; rarity a drop šance. Rarity C/U/R/E/L existují na `Card` i ve statistikách, `Player` má `cards` a `openedPacks`.
2. 🔶 **Otevírání balíčků** (hotovo v Latte; React animace zbývá) (roadmapa: todo) – React komponenta + API endpoint, animace. Připravený `SeasonStatsFacade::addContribution()` pro body do sezóny.
3. ✅ **Obchod** (nákup balíčků do batohu) (roadmapa: todo) – `ShopPresenter` s filtry/stránkováním, `Shop/detail.latte` téměř prázdná; nákup Moon Dust balíčků (cena, potvrzení, odečet MD).
4. **Achievementy** – tabulky `achievement`, `player_achievement`, `faction_achievement`; naseedované `OPEN_1_PACK`, `OPEN_10_PACKS`, `WIN_1_MATCH`, `WIN_10_MATCH`, `DONATE_DUST`. Chybí logika udělování; `WIN_*` předpokládá zápasy, které v kódu nejsou.
5. ⏳ **Přispívání Dustu frakci** (stub `actionAddDust` odstraněn; darování karet hotové, nová myšlenka autora k darování čeká) – `FactionPresenter::actionAddDust()` je stub; šablona počítá s `dustGoal`/`dustProgress`, `cardsGoal`/`cardsProgress` (natvrdo).
6. **Stránka frakce** – level frakce (`xp`, `xpToNext`, `nextLevel` natvrdo), `belongsToFaction` natvrdo, `factionCards` natvrdo, zakomentované `getLastContributions()`, `rank` placeholder; `Faction` má `playersCount` a `factionPoints`. Roadmapa: žebříček hráčů frakce, počet hráčů.
7. **Hlavní přehled frakcí** (roadmapa: todo) – žebříčky, body, počet karet, koeficient, vítězné body.
8. **Uzavírání sezón** – `finalRank` na `PlayerSeasonStats` i `FactionSeasonStats`, zatím nenastavován; živé pořadí přes `getLiveRank()`.
9. **Vlastní avatary** – `Player::$avatar` a cesta `/uploads/avatars/`; upload chybí.
10. ⏳ **Darování karet jinému hráči** (roadmapa: idea) – poslání karty jinému hráči, poplatek v MD.
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

### Příjem Moon Dustu
- **Hlavní zdroj: darování karet** (viz výše).
- **Daily login bonus** (autor, 2026-09-27):
  - **28 odměn**, postup se při vynechaném dni **neresetuje** – počítají se dny, kdy se hráč přihlásil. Po 28. odměně cyklus začíná znovu od 1. dne.
  - Dny 1–6 každého týdne **Moon Dust**, každý **7. den karta**.
  - Dust (+10 za den, další týden o 10 výš):
    - 1. týden: 10, 20, 30, 40, 50, 60 (210)
    - 2. týden: 20, 30, 40, 50, 60, 70 (270)
    - 3. týden: 30, 40, 50, 60, 70, 80 (330)
    - 4. týden: 40, 50, 60, 70, 80, 90 (390)
    - **celkem 1 200 MD za cyklus** (= 12 balíčků)
  - Karty: 7. den **common**, 14. **uncommon**, 21. **rare**, 28. **epic** – legendary jen z balíčků. Karta je náhodná z dané rarity z **frakce hráče + neutrálních** (stejný pool jako balíček).
- Odhad s darováním duplikátů: ~16 balíčků za 1. cyklus, později ~21–24 → cca 1 cyklus garancí (≈ 1,5 legendary) za 28 přihlášení. Odpovídá doporučení analýzy (20 balíčků za 3–4 týdny).
- Implementace: samostatný plán po dokončení balíčků.
