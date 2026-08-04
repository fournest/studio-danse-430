<?php

namespace App\Repository;

use App\Entity\Foyer;
use App\Entity\ReservationCostume;
use App\Enum\StatutReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationCostume>
 */
class ReservationCostumeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationCostume::class);
    }

    public function save(ReservationCostume $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReservationCostume $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Locations facturables rattachées au foyer (hors refusées / annulées).
     *
     * @return list<ReservationCostume>
     */
    public function findFacturablesForFoyer(Foyer $foyer, ?string $saison = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.foyer = :foyer')
            ->andWhere('r.statut NOT IN (:exclus)')
            ->setParameter('foyer', $foyer)
            ->setParameter('exclus', [StatutReservation::REFUSEE, StatutReservation::ANNULEE])
            ->orderBy('r.createdAt', 'ASC');

        if (null !== $saison) {
            $qb->andWhere('(r.saison = :saison OR r.saison IS NULL)')
                ->setParameter('saison', $saison);
        }

        return $qb->getQuery()->getResult();
    }

    public function sumFacturableForFoyer(Foyer $foyer, ?string $saison = null): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.prixTotal), 0)')
            ->andWhere('r.foyer = :foyer')
            ->andWhere('r.statut NOT IN (:exclus)')
            ->setParameter('foyer', $foyer)
            ->setParameter('exclus', [StatutReservation::REFUSEE, StatutReservation::ANNULEE]);

        if (null !== $saison) {
            $qb->andWhere('(r.saison = :saison OR r.saison IS NULL)')
                ->setParameter('saison', $saison);
        }

        return round((float) $qb->getQuery()->getSingleScalarResult(), 2);
    }
}
