<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Player;
use App\Model\Entity\Season;
use App\Model\Entity\User;
use App\Model\Modules\Player\PlayerFacade;
use App\Model\Modules\Player\SeasonStatsFacade;
use App\Model\Service\LevelingService;
use Doctrine\ORM\EntityManagerInterface;

class PlayerPresenter extends BasePresenter
{
    private PlayerFacade $playerFacade;

    private SeasonStatsFacade $seasonStatsFacade;

    private LevelingService $levelingService;

    private EntityManagerInterface $entityManager;

    /** @persistent */
    public ?int $season = null;

    /** @persistent */
    public string $tab = 'player';

    public function __construct(PlayerFacade $playerFacade, LevelingService $levelingService, SeasonStatsFacade $seasonStatsFacade, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->playerFacade = $playerFacade;
        $this->levelingService = $levelingService;
        $this->seasonStatsFacade = $seasonStatsFacade;
    }

    protected function createComponentFactionForm(): \Nette\Application\UI\Form
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        // načteme existující profil kvůli defaultům (nevadí, když ještě není)
        /** @var User $userEntity */
        $userEntity = $this->em->getRepository(User::class)->find($this->getUser()->getId());
        $player = $this->playerFacade->getOrCreateForUser($userEntity);

        $f = new \Nette\Application\UI\Form;

        $f->addText('nickname', 'Jméno')
            ->setRequired('Zadej prosím jméno.')
            ->addRule(\Nette\Forms\Form::MAX_LENGTH, 'Max 32 znaků.', 32)
            ->setDefaultValue($player?->getNickname() ?? '');

        $f->addRadioList('faction', 'Frakce', [
            'ignis' => 'Ignis',
            'vitae' => 'Vitae',
            'noctis' => 'Noctis',
        ])->setRequired('Vyber frakci')
            ->setDefaultValue($player?->getFaction()?->getSlug() ?? null);

        $f->addProtection();
        $f->addSubmit('save', 'Vybrat frakci');

        $f->onSuccess[] = [$this, 'factionFormSucceeded'];
        return $f;
    }

    public function factionFormSucceeded(\Nette\Application\UI\Form $form, \stdClass $v): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        /** @var User $userEntity */
        $userEntity = $this->em->getRepository(User::class)
            ->find($this->getUser()->getId());

        // profil máme i pro úplně nové uživatele
        $player = $this->playerFacade->getOrCreateForUser($userEntity);

        // je to první volba frakce? (použijeme jako trigger bonusu)
        $firstFactionChoice = ($player->getFaction() === null);

        // vše v jedné transakci
        $this->em->wrapInTransaction(function () use ($userEntity, $player, $v, $firstFactionChoice): void {
            // uložit jméno
            $player->setNickname((string) $v->nickname);

            // nastavit frakci přes tvůj service (držíme se tvé architektury)
            $this->playerFacade->setFactionBySlug($userEntity, (string) $v->faction);

            // jednorázový bonus při první volbě frakce
            if ($firstFactionChoice) {
                // předpoklad: v entitě máš pole moonDust + get/set (viz níže)
                $player->setMoonDust(($player->getMoonDust() ?? 0) + 200);
            }

            $this->em->flush();
        });

        $this->flashMessage(
            $firstFactionChoice ? 'Registrace proběhla úspěšně (+200 Moon Dust).' : 'Profil uložen.',
            'success'
        );
        $this->redirect('Player:default');
    }


    /** /player/select – výběr frakce (zobrazí šablonu select.latte) */
    public function renderSelect(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }
        // zapneme déšť jen tady (pokud chceš)
        $this->template->showRain = true;
    }

    /** /player – můj profil */
    public function renderDefault(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->find($this->user->getId());
        $player = $this->playerFacade->getOrCreateForUser($user);

        $this->composePlayerTab($player);
        $this->composeFactionTab($player);
        $this->composeAchievementsTab($player);

        // déšť chceš i na profilu? pak:
        $this->template->showRain = true;
    }

    private function composePlayerTab(Player $player): void
    {
        $need = $this->levelingService->thresholdFor($player->getLevel());
        $xpPct = $this->levelingService->percent($player);

        $slug = $player?->faction?->slug ?? 'noctis';

        $avatarPath = $player->getAvatar()
            ? '/uploads/avatars/' . ltrim((string) $player->getAvatar(), '/')
            : '/assets/avatars/avatar-' . $slug . '.png';

        $this->template->player = $player;
        $this->template->xpToNext = $need;
        $this->template->xp = $player->getXp();
        $this->template->level = $player->getLevel();
        $this->template->xpPct = $xpPct;
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

        // zvolená sezóna = persistent ?: otevřená sezóna ?: první řádek
        $seasonId = $this->season
            ?? $this->em->getRepository(Season::class)->findOneBy(['endAt' => null])?->getId()
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

    /** Tab „Achievementy“ – zatím placeholder */
    private function composeAchievementsTab(Player $player): void
    {
        $this->template->achievements = $player->getAchievements();
        // později sem načti detailní seznam, filtry, atd.
    }
}
