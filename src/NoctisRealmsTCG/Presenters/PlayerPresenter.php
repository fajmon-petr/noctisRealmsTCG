<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Player;
use App\Model\Modules\Player\PlayerFacade;
use App\Model\Modules\Player\SeasonStatsFacade;
use App\Model\Modules\Season\SeasonFacade;
use App\Model\Service\LevelingService;
use Nette\Application\UI\Form;

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
