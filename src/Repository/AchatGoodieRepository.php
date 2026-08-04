<?php

namespace App\Repository;

use App\Entity\AchatGoodie;
use App\Entity\Foyer;
use App\Entity\Goodie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AchatGoodie>
 */
class AchatGoodieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AchatGoodie::class);
    }

    /**
     * @return list<AchatGoodie>
     */
    public function findForFoyerSaison(Foyer $foyer, string $saison): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.foyer = :foyer')
            ->andWhere('a.saison = :saison')
            ->setParameter('foyer', $foyer)
            ->setParameter('saison', $saison)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function sumForFoyerSaison(Foyer $foyer, string $saison): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.prixTotal), 0)')
            ->andWhere('a.foyer = :foyer')
            ->andWhere('a.saison = :saison')
            ->setParameter('foyer', $foyer)
            ->setParameter('saison', $saison)
            ->getQuery()
            ->getSingleScalarResult();

        return round((float) $result, 2);
    }

    public function findForFoyerGoodieSaisonTaille(
        Foyer $foyer,
        Goodie $goodie,
        string $saison,
        ?string $taille,
    ): ?AchatGoodie {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.foyer = :foyer')
            ->andWhere('a.goodie = :goodie')
            ->andWhere('a.saison = :saison')
            ->setParameter('foyer', $foyer)
            ->setParameter('goodie', $goodie)
            ->setParameter('saison', $saison)
            ->setMaxResults(1);

        if (null === $taille || '' === $taille) {
            $qb->andWhere('a.taille IS NULL');
        } else {
            $qb->andWhere('a.taille = :taille')
                ->setParameter('taille', $taille);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
