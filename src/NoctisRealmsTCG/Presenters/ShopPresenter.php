<?php declare(strict_types=1);

namespace App\Presenters;

use Doctrine\ORM\EntityManagerInterface;

final class ShopPresenter extends BasePresenter
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManagerInterface) {
        parent::__construct($entityManagerInterface);
    }

    public function renderDefault(?string $category = null): void
    {
        // --- vstupní filtry ---
        $q       = (string) $this->getParameter('q', '');
        $rarity  = (string) $this->getParameter('rarity', '');   // C/U/R/E/L
        $faction = (string) $this->getParameter('faction', '');  // Ignis/Vitae/Noctis
        $sort    = (string) $this->getParameter('sort', 'new');  // new|price_asc|price_desc|name_asc
        $page    = max(1, (int) $this->getParameter('page', 1));
        $perPage = 24;

        // --- základní dotaz ---
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
           ->from('App\\NoctisRealmsTCG\\Model\\Entity\\Card', 'c');

        // Hledání podle názvu (uprav pole, pokud se nejmenuje "name"):
        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q')->setParameter('q', "%$q%");
        }

        // Filtrování podle rarity (pokud máš jiný typ, uprav):
        if ($rarity !== '') {
            $qb->andWhere('c.rarity = :rarity')->setParameter('rarity', $rarity);
        }

        // Filtrování podle frakce:
        // Varianta A (frakce je string/sloupec na Card): c.faction = :f
        // Varianta B (frakce je vztah na entitu Faction): c.faction = :factionEntity  -> pak nahraď za join & find() dle code/slug
        if ($faction !== '') {
            // -> pokud máš na kartě string pole 'faction' (Ignis/Vitae/Noctis), nech takto:
            $qb->andWhere('c.faction = :faction')->setParameter('faction', $faction);

            // -> pokud je to ManyToOne na Faction, pak použij místo řádku výše:
            // $qb->join('c.faction', 'f')->andWhere('f.code = :faction')->setParameter('faction', $faction);
        }

        // Řazení (přepni pole podle své entity; fallback na id):
        switch ($sort) {
            case 'price_asc':
                // pokud nemáš price na kartu, klidně to zakomentuj nebo přepni default
                $qb->orderBy('c.price', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('c.price', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('c.name', 'ASC');
                break;
            case 'new':
            default:
                // pokud bys neměl createdAt, použij id DESC
                $qb->orderBy('c.createdAt', 'DESC');
                // $qb->orderBy('c.id', 'DESC');
        }

        // Stránkování
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $items = $qb->getQuery()->getResult();

        // (volitelně) total pro stránkování – rychlý count (druhá query)
        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy')
                ->resetDQLPart('select')
                ->resetDQLPart('groupBy')
                ->select('COUNT(c.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $this->template->items   = $items;
        $this->template->filters = [
            'search'  => $q,
            'rarity'  => $rarity,
            'faction' => $faction,
            'sort'    => $sort,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
        ];
    }

    public function renderDetail(int $id): void
    {
        // detail může zatím zobrazovat Card; později klidně Product entitu
        $card = $this->em->find('App\\NoctisRealmsTCG\\Model\\Entity\\Card', $id);
        if (!$card) {
            $this->error('Item not found');
        }
        $this->template->item = $card;
    }
}
