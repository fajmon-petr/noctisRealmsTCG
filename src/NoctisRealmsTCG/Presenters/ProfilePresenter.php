<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\PlayerFactionStats;
use App\Model\Entity\Profile;
use App\Model\Entity\Season;
use App\Model\Entity\User;
use App\Model\Modules\Profile\ProfileFacade;
use App\Model\Service\LevelingService;
use Doctrine\ORM\EntityManagerInterface;

final class ProfilePresenter extends BasePresenter
{
    private ProfileFacade $profileFacade;

    private LevelingService $levelingService;

    private EntityManagerInterface $entityManager;

    /** @persistent */
    public ?int $season = null;

    /** @persistent */
    public string $tab = 'profile';

    public function __construct(ProfileFacade $profileFacade, LevelingService $levelingService, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->profileFacade = $profileFacade;
        $this->levelingService = $levelingService;
    }

    protected function createComponentFactionForm(): \Nette\Application\UI\Form
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        // načteme existující profil kvůli defaultům (nevadí, když ještě není)
        /** @var User $userEntity */
        $userEntity = $this->em->getRepository(User::class)->find($this->getUser()->getId());
        $profile = $this->profileFacade->getOrCreateForUser($userEntity);

        $f = new \Nette\Application\UI\Form;

        $f->addText('nickname', 'Jméno')
            ->setRequired('Zadej prosím jméno.')
            ->addRule(\Nette\Forms\Form::MAX_LENGTH, 'Max 32 znaků.', 32)
            ->setDefaultValue($profile?->getNickname() ?? '');

        $f->addRadioList('faction', 'Frakce', [
            'ignis' => 'Ignis',
            'vitae' => 'Vitae',
            'noctis' => 'Noctis',
        ])->setRequired('Vyber frakci')
            ->setDefaultValue($profile?->getFaction()?->getSlug() ?? null);

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
        $profile = $this->profileFacade->getOrCreateForUser($userEntity);

        // je to první volba frakce? (použijeme jako trigger bonusu)
        $firstFactionChoice = ($profile->getFaction() === null);

        // vše v jedné transakci
        $this->em->wrapInTransaction(function () use ($userEntity, $profile, $v, $firstFactionChoice): void {
            // uložit jméno
            $profile->setNickname((string) $v->nickname);

            // nastavit frakci přes tvůj service (držíme se tvé architektury)
            $this->profileFacade->setFactionBySlug($userEntity, (string) $v->faction);

            // jednorázový bonus při první volbě frakce
            if ($firstFactionChoice) {
                // předpoklad: v entitě máš pole moonDust + get/set (viz níže)
                $profile->setMoonDust(($profile->getMoonDust() ?? 0) + 200);
            }

            $this->em->flush();
        });

        $this->flashMessage(
            $firstFactionChoice ? 'Registrace proběhla úspěšně (+200 Moon Dust).' : 'Profil uložen.',
            'success'
        );
        $this->redirect('Profile:default');
    }


    /** /profile/select – výběr frakce (zobrazí šablonu select.latte) */
    public function renderSelect(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }
        // zapneme déšť jen tady (pokud chceš)
        $this->template->showRain = true;
    }

    /** /profile – můj profil */
    public function renderDefault(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        $user = $this->em->getRepository(User::class)->find($this->user->getId());
        $profile = $this->em->getRepository(Profile::class)
            ->findOneBy(['user' => $user]);

        $this->composeProfileTab($profile);
        $this->composeFactionTab($profile, $this->season);
        $this->composeAchievementsTab($profile);

        // déšť chceš i na profilu? pak:
        $this->template->showRain = true;
    }

    private function composeProfileTab(Profile $profile): void
    {
        $need = $this->levelingService->thresholdFor($profile->getLevel());
        $xpPct = $this->levelingService->percent($profile);

        $this->template->profile = $profile;
        $this->template->xpToNext = $need;
        $this->template->xp = $profile->getXp();
        $this->template->level = $profile->getLevel();
        $this->template->xpPct = $xpPct;
    }

    /** Tab „Frakce“: sezóny, moje stats v sezóně/lifetime, leaderboard */
    private function composeFactionTab(Profile $profile, ?int $seasonId): void
    {
        // seznam sezón (pro select)
        $seasons = $this->em->getRepository(Season::class)
            ->findBy([], ['startAt' => 'DESC']);

        // moje stats (lifetime nebo vybraná sezóna)
        $crit = [
            'profile' => $profile,
            'season' => $seasonId ? $this->em->getRepository(Season::class)->find($seasonId) : null,
        ];
        /** @var PlayerFactionStats|null $stats */
        $stats = $this->em->getRepository(PlayerFactionStats::class)->findOneBy($crit);

        // do šablony:
        $this->template->seasons = $seasons;
        $this->template->seasonId = $seasonId;
        $this->template->f = [
            'points' => $stats?->getPointsTotal() ?? 0,
            'common' => $stats?->getCommon() ?? 0,
            'uncommon' => $stats?->getUncommon() ?? 0,
            'rare' => $stats?->getRare() ?? 0,
            'epic' => $stats?->getEpic() ?? 0,
            'legendary' => $stats?->getLegendary() ?? 0,
        ];
    }

    /** Tab „Achievementy“ – zatím placeholder */
    private function composeAchievementsTab(Profile $profile): void
    {
        $this->template->achievements = $profile->getAchievements();
        // později sem načti detailní seznam, filtry, atd.
    }
}
