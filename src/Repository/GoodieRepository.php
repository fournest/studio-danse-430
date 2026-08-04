<?php

namespace App\Repository;

use App\Entity\Goodie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Goodie>
 */
class GoodieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Goodie::class);
    }

    /**
     * @return list<Goodie>
     */
    public function findActifsEnStock(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.estActif = true')
            ->andWhere('g.stock > 0')
            ->orderBy('g.categorie', 'ASC')
            ->addOrderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Goodie>
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.estActif = true')
            ->orderBy('g.categorie', 'ASC')
            ->addOrderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Catalogue public filtré par catégorie (valeurs admin : Vêtement, Accessoire, Goodie…).
     *
     * @return list<Goodie>
     */
    public function findActifsByCategorie(?string $categorie = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.estActif = true')
            ->orderBy('g.categorie', 'ASC')
            ->addOrderBy('g.nom', 'ASC');

        if ($categorie) {
            $qb->andWhere('g.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        return $qb->getQuery()->getResult();
    }
}
