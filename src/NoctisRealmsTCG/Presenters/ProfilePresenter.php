<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\User;
use App\Model\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\Service\LevelingService;

final class ProfilePresenter extends BasePresenter
{
    public EntityManagerInterface $em;
    public ProfileService $profileService;
    public LevelingService $levelingService;

    public function __construct(EntityManagerInterface $em, ProfileService $profileService, LevelingService $levelingService)
    {
        $this->em = $em;
        $this->profileService = $profileService;
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
        $profile = $this->profileService->getOrCreateForUser($userEntity);

        $f = new \Nette\Application\UI\Form;

        $f->addText('nickname', 'Jméno')
        ->setRequired('Zadej prosím jméno.')
        ->addRule(\Nette\Forms\Form::MAX_LENGTH, 'Max 32 znaků.', 32)
        ->setDefaultValue($profile?->getNickname() ?? '');

        $f->addRadioList('faction', 'Frakce', [
            'ignis'  => 'Ignis',
            'vitae'  => 'Vitae',
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
        $profile = $this->profileService->getOrCreateForUser($userEntity);

        // je to první volba frakce? (použijeme jako trigger bonusu)
        $firstFactionChoice = ($profile->getFaction() === null);

        // vše v jedné transakci
        $this->em->wrapInTransaction(function () use ($userEntity, $profile, $v, $firstFactionChoice): void {
            // uložit jméno
            $profile->setNickname((string) $v->nickname);

            // nastavit frakci přes tvůj service (držíme se tvé architektury)
            $this->profileService->setFactionBySlug($userEntity, (string) $v->faction);

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
        $this->template->showRain = true;

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        $identity = $this->getUser()->getIdentity();
        /** @var User $userEntity */
        $userEntity = $this->em->getRepository(User::class)->find($identity->id);
        $profile = $this->profileService->getOrCreateForUser($userEntity);
        
        $this->template->profile = $profile;

        $need = $this->levelingService->thresholdFor($profile->getLevel());
        $this->template->xpToNext = $need;
        $this->template->xp = $profile->getXp();
        $this->template->level = $profile->getLevel();
        $this->template->xpPct = $this->levelingService->percent($profile);

        // déšť chceš i na profilu? pak:
        $this->template->showRain = true;
    }
}
