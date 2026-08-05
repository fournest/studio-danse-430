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
     * Dernières actualités publiées (gestion manuelle back-office).
     *
     * @return list<Actualite>
     */
    public function findLatest(int $limit = 3): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = true')
            ->andWhere('a.publierDansFil = true')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Actualite>
     */
    public function findAllPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = true')
            ->andWhere('a.publierDansFil = true')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Actualite
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.isPublished = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
