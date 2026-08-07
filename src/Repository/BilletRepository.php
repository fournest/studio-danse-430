<?php

namespace App\Repository;

use App\Entity\Billet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Billet>
 */
class BilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Billet::class);
    }

    /**
     * @return list<Billet>
     */
    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('b.event', 'e')->addSelect('e')
            ->orderBy('e.dateHeure', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByToken(string $token): ?Billet
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.token = :token')
            ->setParameter('token', $token)
            ->leftJoin('b.event', 'e')->addSelect('e')
            ->leftJoin('b.user', 'u')->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
