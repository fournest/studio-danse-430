<?php

namespace App\Repository;

use App\Entity\Gala;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gala>
 */
class GalaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gala::class);
    }

    /**
     * Retourne les galas à venir, du plus proche au plus lointain.
     *
     * @return Gala[]
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.dateHeure >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('g.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
