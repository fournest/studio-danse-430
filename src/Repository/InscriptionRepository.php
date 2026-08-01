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

    /**
     * Dernières inscriptions dont le danseur et le cours existent encore.
     *
     * @return list<Inscription>
     */
    public function findRecentWithRelations(int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.danseur', 'd')->addSelect('d')
            ->innerJoin('i.cours', 'c')->addSelect('c')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Inscriptions pointant vers un danseur ou un cours supprimé.
     *
     * @return list<Inscription>
     */
    public function findOrphans(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.danseur', 'd')
            ->leftJoin('i.cours', 'c')
            ->andWhere('d.id IS NULL OR c.id IS NULL')
            ->getQuery()
            ->getResult();
    }
}
