<?php

namespace App\Repository;

use App\Entity\Actualite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actualite>
 */
class ActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actualite::class);
    }

    /**
     * Récupère les dernières actualités des réseaux triées par date décroissante
     * * @param int $limit Le nombre maximum d'actualités à afficher
     * @return Actualite[]
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}