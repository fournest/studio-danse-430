<?php

namespace App\Repository;

use App\Entity\Costume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Costume>
 */
class CostumeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Costume::class);
    }

    /**
     * Catalogue public hors gala, avec filtres optionnels.
     *
     * @return list<Costume>
     */
    public function findDisponiblesHorsGala(?string $theme = null, ?string $taille = null, ?string $genre = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.disponibleHorsGala = true')
            ->andWhere('c.quantite > 0')
            ->orderBy('c.nom', 'ASC');

        if ($theme) {
            $qb->andWhere('c.theme = :theme')
                ->setParameter('theme', $theme);
        }

        if ($taille) {
            $qb->andWhere('c.taille = :taille')
                ->setParameter('taille', $taille);
        }

        if ($genre) {
            $qb->andWhere('c.genre = :genre')
                ->setParameter('genre', $genre);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<string>
     */
    public function findDistinctThemesHorsGala(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('DISTINCT c.theme')
            ->andWhere('c.disponibleHorsGala = true')
            ->andWhere('c.theme IS NOT NULL')
            ->andWhere("c.theme != ''")
            ->orderBy('c.theme', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_filter($rows));
    }

    /**
     * @return list<string>
     */
    public function findDistinctTaillesHorsGala(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('DISTINCT c.taille')
            ->andWhere('c.disponibleHorsGala = true')
            ->andWhere('c.taille IS NOT NULL')
            ->andWhere("c.taille != ''")
            ->orderBy('c.taille', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_filter($rows));
    }
}
