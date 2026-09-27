<?php declare(strict_types=1);

namespace App\Model\Modules\Card;

use App\Model\Entity\Card;
use App\Model\Entity\Rarity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CardFacade
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getById(int $id): ?Card
    {
        return $this->em->find(Card::class, $id);
    }

    /** @return Rarity[] od nejběžnější po nejvzácnější */
    public function getRarities(): array
    {
        return $this->em->getRepository(Rarity::class)->findBy([], ['sortOrder' => 'ASC']);
    }

    /**
     * Vyhledávání karet s filtry a stránkováním.
     *
     * @param string $rarity kód rarity (C/U/R/E/L)
     * @param string $sort new|name_asc
     * @return Paginator<Card>
     */
    public function search(string $q, string $rarity, string $factionSlug, string $sort, int $page, int $perPage): Paginator
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c', 'f', 'r')
            ->from(Card::class, 'c')
            ->leftJoin('c.faction', 'f')
            ->join('c.rarity', 'r');

        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q')->setParameter('q', "%$q%");
        }

        if ($rarity !== '') {
            $qb->andWhere('r.code = :rarity')->setParameter('rarity', $rarity);
        }

        if ($factionSlug !== '') {
            $qb->andWhere('f.slug = :faction')->setParameter('faction', $factionSlug);
        }

        match ($sort) {
            'name_asc' => $qb->orderBy('c.name', 'ASC'),
            default    => $qb->orderBy('c.id', 'DESC'),
        };

        $qb->setFirstResult((max(1, $page) - 1) * $perPage)->setMaxResults($perPage);

        return new Paginator($qb);
    }
}
