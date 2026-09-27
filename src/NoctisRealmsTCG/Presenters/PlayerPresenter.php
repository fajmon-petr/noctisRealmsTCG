<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Player;
use App\Model\Entity\PlayerPack;
use App\Model\Entity\Rarity;
use App\Model\Modules\Card\CardFacade;
use App\Model\Modules\Card\CollectionFacade;
use App\Model\Modules\Card\DonationException;
use App\Model\Modules\Pack\PackException;
use App\Model\Modules\Pack\PackFacade;
use App\Model\Modules\Player\PlayerFacade;
use App\Model\Modules\Player\SeasonStatsFacade;
use App\Model\Modules\Season\SeasonFacade;
use App\Model\Service\LevelingService;
use App\Model\Service\Pack\PackRules;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

class PlayerPresenter extends SecuredPresenter
{
    /** @persistent */
    public ?int $season = null;

    /** @persistent */
    public string $tab = 'player';

    private Player $player;

    public function __construct(
        private LevelingService $levelingService,
        private SeasonStatsFacade $seasonStatsFacade,
        private SeasonFacade $seasonFacade,
        private PackFacade $packFacade,
        private CollectionFacade $collectionFacade,
        private CardFacade $cardFacade,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        // profil máme i pro úplně nové uživatele
        $this->player = $this->playerFacade->getForUserId((int) $this->getUser()->getId());
    }

    protected function createComponentFactionForm(): Form
    {
        $f = new Form;

        $f->addText('nickname', 'Jméno')
            ->setRequired('Zadej prosím jméno.')
            ->addRule(Form::MaxLength, 'Max 32 znaků.', 32)
            ->setDefaultValue($this->player->getNickname() ?? '');

        $f->addRadioList('faction', 'Frakce', [
            'ignis' => 'Ignis',
            'vitae' => 'Vitae',
            'noctis' => 'Noctis',
        ])->setRequired('Vyber frakci')
            ->setDefaultValue($this->player->getFaction()?->getSlug());

        $f->addProtection();
        $f->addSubmit('save', 'Vybrat frakci');

        $f->onSuccess[] = [$this, 'factionFormSucceeded'];
        return $f;
    }

    public function factionFormSucceeded(Form $form, \stdClass $v): void
    {
        $firstFactionChoice = $this->playerFacade->saveProfile($this->player, (string) $v->nickname, (string) $v->faction);

        $this->flashMessage(
            $firstFactionChoice
                ? 'Registrace proběhla úspěšně (+' . PlayerFacade::FIRST_FACTION_BONUS . ' Moon Dust).'
                : 'Profil uložen.',
            'success'
        );
        $this->redirect('Player:default');
    }

    /** /player/backpack – neotevřené balíčky a postup ke garancím */
    public function renderBackpack(): void
    {
        $this->composeBackpack();
        $this->template->showRain = true;
    }

    /** /player/collection – kolekce karet a darování frakci */
    public function renderCollection(string $rarity = ''): void
    {
        $collection = $this->collectionFacade->getCollection($this->player);
        if ($rarity !== '') {
            $collection = array_values(array_filter(
                $collection,
                fn($owned) => $owned->getCard()->getRarity()->getCode() === $rarity,
            ));
        }

        $this->template->collection = $collection;
        $this->template->rarityFilter = $rarity;
        $this->template->rarities = $this->cardFacade->getRarities();
        $this->template->player = $this->player;
        $this->template->season = $this->seasonFacade->getCurrentSeason();
        $this->template->showRain = true;
    }

    /** Formulář „Darovat“ pro každou kartu v kolekci – donateForm-<id karty> */
    protected function createComponentDonateForm(): Multiplier
    {
        return new Multiplier(function (string $cardId): Form {
            $form = new Form;
            $form->addInteger('count', 'Počet')
                ->setDefaultValue(1)
                ->setRequired()
                ->addRule(Form::Min, 'Daruj alespoň jednu kartu.', 1);
            $form->addProtection();
            $form->addSubmit('donate', 'Darovat');
            $form->onSuccess[] = fn(Form $form, \stdClass $values) => $this->donateFormSucceeded((int) $cardId, $values->count);
            return $form;
        });
    }

    private function donateFormSucceeded(int $cardId, int $count): void
    {
        $card = $this->cardFacade->getById($cardId);
        if ($card === null) {
            $this->error('Karta neexistuje.');
        }

        try {
            $result = $this->collectionFacade->donate($this->player, $card, $count);
        } catch (DonationException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect('this');
        }

        $this->flashMessage(sprintf(
            'Daroval jsi %d× %s frakci %s: +%d Moon Dustu, +%d bodů do žebříčku.',
            $result->count,
            $result->card->getName(),
            $result->faction->getName(),
            $result->moonDust,
            $result->points,
        ), 'success');
        $this->redirect('this');
    }

    /** /player/opened?ids=1-2-3 – výsledek otevření jednoho nebo více balíčků */
    public function renderOpened(string $ids = ''): void
    {
        $packs = [];
        foreach (array_unique(array_filter(array_map('intval', explode('-', $ids)))) as $id) {
            $pack = $this->packFacade->getPlayerPack($this->player, $id);
            if ($pack !== null && $pack->isOpened()) {
                $packs[] = $pack;
            }
        }
        if ($packs === []) {
            $this->error('Balíček nenalezen.');
        }

        // souhrn rarit napříč otevřenými balíčky (od nejvzácnější)
        $summary = [];
        foreach ($packs as $pack) {
            foreach ($pack->getCards() as $packCard) {
                $rarity = $packCard->getCard()->getRarity();
                $summary[$rarity->getCode()] ??= ['rarity' => $rarity, 'count' => 0];
                $summary[$rarity->getCode()]['count']++;
            }
        }
        uasort($summary, fn(array $a, array $b) => $b['rarity']->getSortOrder() <=> $a['rarity']->getSortOrder());

        $this->template->packs = $packs;
        $this->template->raritySummary = $summary;
        $this->template->lastFaction = end($packs)->getFaction();
        $this->composeBackpack();
        $this->template->showRain = true;
    }

    /** /player/pack/<id> – starší odkaz na jeden balíček */
    public function actionPack(int $id): void
    {
        $this->redirect('opened', ['ids' => (string) $id]);
    }

    /** Batoh + postup ke garancím (batoh i stránka výsledku) */
    private function composeBackpack(): void
    {
        $summary = $this->packFacade->getBackpackSummary($this->player);
        $opened = $this->player->getOpenedPacks();

        $this->template->player = $this->player;
        $this->template->packGroups = $summary;
        $this->template->packsTotal = array_sum(array_column($summary, 'count'));
        $this->template->maxOpenAtOnce = PackRules::MAX_OPEN_AT_ONCE;
        $this->template->openedPacks = $opened;
        $this->template->nextGuarantee = PackRules::guaranteeFor($opened + 1);
        $this->template->guarantees = [
            'rare+' => PackRules::packsUntilGuarantee($opened, Rarity::RARE),
            'epic+' => PackRules::packsUntilGuarantee($opened, Rarity::EPIC),
            'legendary' => PackRules::packsUntilGuarantee($opened, Rarity::LEGENDARY),
        ];
    }

    /**
     * Formulář otevírání – openPackForm-<slug frakce> nebo openPackForm-all (všechny frakce).
     * „Otevřít“ otevře zadaný počet nejstarších balíčků, „Otevřít vše“ všechny (max. MAX_OPEN_AT_ONCE).
     */
    protected function createComponentOpenPackForm(): Multiplier
    {
        return new Multiplier(function (string $key): Form {
            $form = new Form;
            $form->addInteger('count', 'Počet')
                ->setDefaultValue(1)
                ->setRequired()
                ->addRule(Form::Range, 'Najednou lze otevřít %d až %d balíčků.', [1, PackRules::MAX_OPEN_AT_ONCE]);
            $form->addProtection();
            $form->addSubmit('open', 'Otevřít');
            $openAll = $form->addSubmit('openAll', 'Otevřít vše')->setValidationScope([]);
            $form->onSuccess[] = function (Form $form, \stdClass $values) use ($key, $openAll): void {
                $this->openPackFormSucceeded($key, $openAll->isSubmittedBy() ? null : $values->count);
            };
            return $form;
        });
    }

    /** @param int|null $count null = všechny (max. MAX_OPEN_AT_ONCE) */
    private function openPackFormSucceeded(string $key, ?int $count): void
    {
        $summary = $this->packFacade->getBackpackSummary($this->player);
        $faction = $key === 'all' ? null : ($summary[$key]['faction'] ?? null);
        $available = $key === 'all' ? array_sum(array_column($summary, 'count')) : ($summary[$key]['count'] ?? 0);

        if ($available === 0) {
            $this->flashMessage('V batohu nemáš žádný takový balíček.', 'warning');
            $this->redirect('backpack');
        }

        $requested = min($count ?? $available, $available, PackRules::MAX_OPEN_AT_ONCE);

        try {
            $opened = $this->packFacade->openMany($this->player, $faction, $requested);
        } catch (PackException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect('backpack');
        }

        if (count($opened) < $requested) {
            $this->flashMessage(sprintf('Otevřeno %d z %d balíčků – zbytek se otevřít nepodařilo.', count($opened), $requested), 'warning');
        }
        $this->redirect('opened', ['ids' => implode('-', array_map(fn(PlayerPack $p) => $p->getId(), $opened))]);
    }

    /** /player/select – výběr frakce (zobrazí šablonu select.latte) */
    public function renderSelect(): void
    {
        $this->template->showRain = true;
    }

    /** /player – můj profil */
    public function renderDefault(): void
    {
        $this->composePlayerTab($this->player);
        $this->composeFactionTab($this->player);
        $this->composeAchievementsTab($this->player);

        $this->template->showRain = true;
    }

    private function composePlayerTab(Player $player): void
    {
        $slug = $player->getFaction()?->getSlug() ?? 'noctis';

        $avatarPath = $player->getAvatar()
            ? '/uploads/avatars/' . ltrim($player->getAvatar(), '/')
            : '/assets/avatars/avatar-' . $slug . '.png';

        $this->template->player = $player;
        $this->template->xpToNext = $this->levelingService->thresholdFor($player->getLevel());
        $this->template->xp = $player->getXp();
        $this->template->level = $player->getLevel();
        $this->template->xpPct = $this->levelingService->percent($player);
        $this->template->avatarPath = $avatarPath;
        $this->template->backpackCount = array_sum(array_column($this->packFacade->getBackpackSummary($player), 'count'));
    }

    /** Tab „Frakce“: sezóny, moje stats v sezóně/lifetime, leaderboard */
    private function composeFactionTab(Player $player): void
    {
        $rows = $this->seasonStatsFacade->allSeasonsWithMyStats($player);

        // seznam pro <select>
        $this->template->seasons = array_map(
            fn(array $r) => (object) ['id' => (int) $r['seasonId'], 'name' => $r['seasonName']],
            $rows
        );

        // zvolená sezóna = persistent ?: probíhající sezóna ?: první řádek
        $seasonId = $this->season
            ?? $this->seasonFacade->getCurrentSeason()?->getId()
            ?? (isset($rows[0]) ? (int) $rows[0]['seasonId'] : null);
        $this->template->season = $seasonId;

        // najdi řádek vybrané sezóny (nebo první jako fallback)
        $current = $rows[0] ?? [];
        foreach ($rows as $r) {
            if ((int) $r['seasonId'] === $seasonId) {
                $current = $r;
                break;
            }
        }

        $fallbackSlug = $player->getFaction()?->getSlug() ?? 'noctis';
        $this->template->seasonFactionSlug = $current['factionSlug'] ?? $fallbackSlug;

        // frakce pro styling headeru (bez dalšího dotazu)
        $this->template->seasonFaction = (object) [
            'name' => $current['factionName'] ?? null,
            'slug' => $current['factionSlug'] ?? null,
            'color' => $current['factionColor'] ?? '#6A1B9A',
            'rank' => $current['finalRank']
                ?? ($seasonId !== null ? $this->seasonStatsFacade->getLiveRank($player->getId(), $seasonId) : null),
            'start' => isset($current['startAt']) ? $current['startAt']->format('d. m. Y') : null,
            'end' => isset($current['endAt']) ? $current['endAt']->format('d. m. Y') : 'současnost',
        ];

        // čísla pro karty
        $this->template->f = [
            'points' => (int) ($current['points'] ?? 0),
            'common' => (int) ($current['common'] ?? 0),
            'uncommon' => (int) ($current['uncommon'] ?? 0),
            'rare' => (int) ($current['rare'] ?? 0),
            'epic' => (int) ($current['epic'] ?? 0),
            'legendary' => (int) ($current['legendary'] ?? 0),
        ];
    }

    /** Tab „Achievementy“ – zatím jen počet, seznam je WIP */
    private function composeAchievementsTab(Player $player): void
    {
        $this->template->achievements = $player->getAchievements();
    }
}
