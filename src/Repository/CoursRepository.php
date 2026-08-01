<?php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * Retourne les cours triés par jour puis par heure.
     *
     * @return Cours[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.jour', 'ASC')
            ->addOrderBy('c.heure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cours éligibles selon l'année de naissance du danseur.
     *
     * @return Cours[]
     */
    public function findEligibleForBirthYear(?int $anneeNaissance): array
    {
        if (null === $anneeNaissance) {
            return $this->findAllOrdered();
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.anneeNaissanceMin IS NULL OR c.anneeNaissanceMin <= :annee')
            ->andWhere('c.anneeNaissanceMax IS NULL OR c.anneeNaissanceMax >= :annee')
            ->setParameter('annee', $anneeNaissance)
            ->orderBy('c.jour', 'ASC')
            ->addOrderBy('c.heure', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
