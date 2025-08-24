<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Service\UserService;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use App\Model\Service\ProfileService;

final class HomePresenter extends BasePresenter
{
    private UserService $userService;

    private ProfileService $profileService;

    public function __construct(UserService $userService, ProfileService $profileService) { 
        $this->userService = $userService;
        $this->profileService = $profileService;
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
        $f->addPassword('password2','Potvrzení hesla')->setRequired()->addRule($f::Equal, 'Hesla se neshodují', $f['password']);
        $f->addProtection();
        $f->addSubmit('send', 'Registrovat');
        $f->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $f;
    }

    public function registerFormSucceeded(Form $form, \stdClass $v): void
    {
        if ($this->userService->findByEmail($v->email)) {
            $form->addError('E-mail už existuje.');
            return;
        }

        $userEntity = $this->userService->register($v->email, $v->password);
        $this->getUser()->login($v->email, $v->password);

        $this->profileService->getOrCreateForUser($userEntity);

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
            ['title' => 'Balíčky Noctis 0.1', 'text' => 'První testovací edice je na cestě.'],
            ['title' => 'Výběr frakce', 'text' => 'Ignis • Vitae • Noctis — už brzy.'],
        ];
    }
}
