<?php declare(strict_types=1);

namespace App\Model\Modules\Season;

use App\Model\Entity\Season;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

class SeasonFacade
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getById(int $id): ?Season
    {
        return $this->em->find(Season::class, $id);
    }

    /** Sezóna, která právě probíhá (začala a ještě neskončila) */
    public function getCurrentSeason(): ?Season
    {
        return $this->em->getRepository(Season::class)->createQueryBuilder('s')
            ->where('s.startAt <= :now')
            ->andWhere('(s.endAt IS NULL OR s.endAt >= :now)')
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('s.startAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
