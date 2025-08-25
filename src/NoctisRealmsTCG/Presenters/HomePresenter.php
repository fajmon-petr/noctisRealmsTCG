<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Modules\Profile\ProfileFacade;
use App\Model\Modules\User\UserFacade;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

final class HomePresenter extends BasePresenter
{
    private UserFacade $userFacade;

    private ProfileFacade $profileFacade;

    private EntityManagerInterface $entityManager;

    public function __construct(UserFacade $userFacade, ProfileFacade $profileFacade, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->userFacade = $userFacade;
        $this->profileFacade = $profileFacade;
    }

    /** Přihlášení */
    protected function createComponentSignInForm(): Form
    {
        $f = new Form;
        $f->addEmail('email', 'E-mail:')->setRequired();
        $f->addPassword('password', 'Heslo:')->setRequired();
        $f->addProtection();
        $f->addSubmit('send', 'Přihlásit');
        $f->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $f;
    }

    public function signInFormSucceeded(Form $form, \stdClass $v): void
    {
        try {
            $this->getUser()->login($v->email, $v->password);
            $this->getUser()->setExpiration('30 minutes');

            $this->flashMessage('Vítej zpět!', 'success');
            $this->redirect('Profile:default');

        } catch (AuthenticationException $e) {
            // Zobrazíme chybu jako flash místo chybového pole ve formuláři
            $this->flashMessage('Neplatný e-mail nebo heslo.', 'error');
            $this->redirect('this'); // refresh stránky, aby se flash zobrazil
        }
    }

    /** Registrace */
    protected function createComponentRegisterForm(): Form
    {
        $f = new Form;
        $f->addEmail('email', 'E-mail:')->setRequired();
        $f->addPassword('password', 'Heslo:')
            ->setRequired()
            ->addRule($f::MinLength, 'Min. 6 znaků', 6);
        $f->addPassword('password2', 'Potvrzení hesla')->setRequired()->addRule($f::Equal, 'Hesla se neshodují', $f['password']);
        $f->addProtection();
        $f->addSubmit('send', 'Registrovat');
        $f->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $f;
    }

    public function registerFormSucceeded(Form $form, \stdClass $v): void
    {
        if ($this->userFacade->findByEmail($v->email)) {
            $form->addError('E-mail už existuje.');
            return;
        }

        $userEntity = $this->userFacade->register($v->email, $v->password);
        $this->getUser()->login($v->email, $v->password);

        $this->profileFacade->getOrCreateForUser($userEntity);

        $this->flashMessage('Úspěšná registrace. Vyber si frakci.', 'success');
        $this->redirect('Profile:select');
    }

    public function actionLogout(): void
    {
        $this->getUser()->logout(true); // true = zruší i persistentní session
        $this->flashMessage('Byl jsi úspěšně odhlášen.', 'success');
        $this->redirect('Home:default');
    }

    public function renderDefault(): void
    {
        $this->template->showRain = true;

        $this->template->news = [
            // === FRACE ===
            ['title' => 'Tab „Frakce“ na profilu', 'text' => 'Základní info o zvolené frakci + mini žebříček.', 'status' => 'todo'],
            ['title' => 'Stránka Frakce', 'text' => 'Detail frakce: žebříček hráčů, level frakce, počet hráčů, popis.', 'status' => 'todo'],
            ['title' => 'Darování karet', 'text' => 'Mechanika pro poslání karty jinému hráči (ověření, poplatek v Moon Dust).', 'status' => 'idea'],

            // === TECH ===
            ['title' => 'Integrace Vite + React', 'text' => 'Zavést Vite (TS/React) jako ostrůvky do Latte.', 'status' => 'todo'],

            // === SHOP & PACKS ===
            ['title' => 'Obchod', 'text' => 'UI pro nákup Moon Dust balíčků (cena, potvrzení, odečet MD).', 'status' => 'todo'],
            ['title' => 'Otevírání balíčků (React)', 'text' => 'React komponenta + API endpoint, animace otevření a výpis karet.', 'status' => 'todo'],

            // === EDICE ===
            ['title' => 'Balíčky „Noctis Alpha“', 'text' => 'První testovací edice – definovat rarity a drop šance.', 'status' => 'in-progress'],
        ];
    }

}
