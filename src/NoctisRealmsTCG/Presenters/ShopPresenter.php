<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final class ShopPresenter extends BasePresenter
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);
    }

    public function renderDefault(): void
    {
        // --- vstupní filtry ---
        $q       = (string) $this->getParameter('q', '');
        $rarity  = (string) $this->getParameter('rarity', '');   // C/U/R/E/L
        $faction = (string) $this->getParameter('faction', '');  // slug frakce
        $sort    = (string) $this->getParameter('sort', 'new');  // new|name_asc
        $page    = max(1, (int) $this->getParameter('page', 1));
        $perPage = 24;

        $qb = $this->em->createQueryBuilder()
            ->select('c', 'f')
            ->from(Card::class, 'c')
            ->leftJoin('c.faction', 'f');

        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q')->setParameter('q', "%$q%");
        }

        if ($rarity !== '') {
            $qb->andWhere('c.rarity = :rarity')->setParameter('rarity', $rarity);
        }

        if ($faction !== '') {
            $qb->andWhere('f.slug = :faction')->setParameter('faction', $faction);
        }

        match ($sort) {
            'name_asc' => $qb->orderBy('c.name', 'ASC'),
            default    => $qb->orderBy('c.id', 'DESC'),
        };

        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $paginator = new Paginator($qb);

        $this->template->items   = iterator_to_array($paginator);
        $this->template->filters = [
            'search'  => $q,
            'rarity'  => $rarity,
            'faction' => $faction,
            'sort'    => $sort,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => count($paginator),
        ];
    }

    public function renderDetail(int $id): void
    {
        $card = $this->em->find(Card::class, $id);
        if (!$card) {
            $this->error('Item not found');
        }
        $this->template->item = $card;
    }
}
