<?php declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;

use App\Model\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;

abstract class BasePresenter extends Presenter
{
    public EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->assetsVer = getenv('ASSETS_VER') ?: time(); // v prod nasadíš ASSETS_VER=commit

        $this->template->moonDust = null;

        if ($this->user->isLoggedIn()) {
            // najdi profil přihlášeného uživatele
            $profile = $this->em->getRepository(Profile::class)
                ->findOneBy(['user' => $this->user->getId()]);
            $this->template->moonDust = $profile?->getMoonDust() ?? 0;
        }
    }
}
