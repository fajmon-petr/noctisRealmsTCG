<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Modules\Card\CardFacade;
use App\Model\Modules\Faction\FactionFacade;
use App\Model\Modules\Pack\PackException;
use App\Model\Modules\Pack\PackFacade;
use App\Model\Service\Pack\PackRules;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

final class ShopPresenter extends BasePresenter
{
    private const PER_PAGE = 24;

    public function __construct(
        private CardFacade $cardFacade,
        private FactionFacade $factionFacade,
        private PackFacade $packFacade,
    ) {
        parent::__construct();
    }

    /**
     * @param string $rarity C/U/R/E/L
     * @param string $faction slug frakce
     * @param string $sort new|name_asc
     */
    public function renderDefault(string $q = '', string $rarity = '', string $faction = '', string $sort = 'new', int $page = 1): void
    {
        $page = max(1, $page);
        $paginator = $this->cardFacade->search($q, $rarity, $faction, $sort, $page, self::PER_PAGE);

        $this->template->packFactions = $this->factionFacade->getMainFactions();
        $this->template->packPrice = PackRules::PRICE;
        $this->template->packSize = PackRules::CARDS_PER_PACK;

        $this->template->items   = iterator_to_array($paginator);
        $this->template->rarities = $this->cardFacade->getRarities();
        $this->template->filters = [
            'search'  => $q,
            'rarity'  => $rarity,
            'faction' => $faction,
            'sort'    => $sort,
            'page'    => $page,
            'perPage' => self::PER_PAGE,
            'total'   => count($paginator),
        ];
    }

    public function renderDetail(int $id): void
    {
        $card = $this->cardFacade->getById($id);
        if (!$card) {
            $this->error('Item not found');
        }
        $this->template->item = $card;
    }

    /** Formulář „Koupit“ pro každou frakci zvlášť – buyPackForm-ignis, buyPackForm-vitae, … */
    protected function createComponentBuyPackForm(): Multiplier
    {
        return new Multiplier(function (string $factionSlug): Form {
            $form = new Form;
            $form->addInteger('count', 'Počet')
                ->setDefaultValue(1)
                ->setRequired()
                ->addRule(Form::Range, 'Najednou lze koupit %d až %d balíčků.', [1, PackRules::MAX_BUY_AT_ONCE])
                ->setHtmlAttribute('min', 1)
                ->setHtmlAttribute('max', PackRules::MAX_BUY_AT_ONCE);
            $form->addProtection();
            $form->addSubmit('buy', 'Koupit');
            $form->onSuccess[] = fn(Form $form, \stdClass $values) => $this->buyPackFormSucceeded($factionSlug, $values->count);
            return $form;
        });
    }

    private function buyPackFormSucceeded(string $factionSlug, int $count): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Home:default');
        }

        $faction = $this->factionFacade->getBySlug($factionSlug);
        if ($faction === null) {
            $this->error('Frakce neexistuje.');
        }

        try {
            $player = $this->playerFacade->getForUserId((int) $this->getUser()->getId());
            $this->packFacade->buy($player, $faction, $count);
        } catch (PackException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect('this');
        }

        $this->flashMessage(
            sprintf('Koupeno %d× balíček %s. Najdeš ho v batohu.', $count, $faction->getName()),
            'success',
        );
        $this->redirect('Player:backpack');
    }
}
