<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Modules\Player\PlayerFacade;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    protected PlayerFacade $playerFacade;

    public function injectBase(PlayerFacade $playerFacade): void
    {
        $this->playerFacade = $playerFacade;
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->assetsVer = getenv('ASSETS_VER') ?: time(); // v prod nasadíš ASSETS_VER=commit

        $this->template->moonDust = $this->getUser()->isLoggedIn()
            ? ($this->playerFacade->findByUserId((int) $this->getUser()->getId())?->getMoonDust() ?? 0)
            : null;
    }
}
