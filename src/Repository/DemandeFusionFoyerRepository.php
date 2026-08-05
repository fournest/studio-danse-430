<?php

namespace App\Repository;

use App\Entity\DemandeFusionFoyer;
use App\Entity\Foyer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeFusionFoyer>
 */
class DemandeFusionFoyerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeFusionFoyer::class);
    }

    public function findValidByToken(string $token): ?DemandeFusionFoyer
    {
        $demande = $this->findOneBy(['token' => $token]);

        if (null === $demande || !$demande->isValid()) {
            return null;
        }

        return $demande;
    }

    /**
     * @return list<DemandeFusionFoyer>
     */
    public function findPendingBetween(Foyer $source, Foyer $target): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.foyerSource = :source')
            ->andWhere('d.foyerTarget = :target')
            ->andWhere('d.usedAt IS NULL')
            ->andWhere('d.expiresAt > :now')
            ->setParameter('source', $source)
            ->setParameter('target', $target)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }
}
