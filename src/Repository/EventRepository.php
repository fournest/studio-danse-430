<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Retourne les événements à venir, du plus proche au plus lointain.
     *
     * @return Event[]
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateHeure >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Événement « en cours » (fenêtre J-1 → +6h) ou prochain à venir.
     */
    public function findCurrentOrNext(?\DateTimeImmutable $now = null): ?Event
    {
        $now ??= new \DateTimeImmutable();
        $windowStart = $now->modify('-6 hours');
        $windowEnd = $now->modify('+18 hours');

        $current = $this->createQueryBuilder('e')
            ->andWhere('e.dateHeure BETWEEN :start AND :end')
            ->setParameter('start', $windowStart)
            ->setParameter('end', $windowEnd)
            ->orderBy('e.dateHeure', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($current instanceof Event) {
            return $current;
        }

        return $this->createQueryBuilder('e')
            ->andWhere('e.dateHeure >= :now')
            ->setParameter('now', $now)
            ->orderBy('e.dateHeure', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
