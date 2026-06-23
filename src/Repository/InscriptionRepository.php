<?php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Retourne les inscriptions d'une saison donnée.
     *
     * @return Inscription[]
     */
    public function findBySaison(string $saison): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.saison = :saison')
            ->setParameter('saison', $saison)
            ->getQuery()
            ->getResult();
    }
}
