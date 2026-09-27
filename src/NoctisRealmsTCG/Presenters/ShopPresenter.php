<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Modules\Card\CardFacade;

final class ShopPresenter extends BasePresenter
{
    private const PER_PAGE = 24;

    public function __construct(
        private CardFacade $cardFacade,
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

        $this->template->items   = iterator_to_array($paginator);
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
}
