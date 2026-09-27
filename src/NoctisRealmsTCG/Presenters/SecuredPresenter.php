<?php declare(strict_types=1);

namespace App\Presenters;

/** Presentery dostupné jen přihlášeným uživatelům */
abstract class SecuredPresenter extends BasePresenter
{
    protected function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }
    }
}
