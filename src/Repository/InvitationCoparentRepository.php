<?php

namespace App\Repository;

use App\Entity\InvitationCoparent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InvitationCoparent>
 */
class InvitationCoparentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvitationCoparent::class);
    }

    public function findOneValidByToken(string $token): ?InvitationCoparent
    {
        $invitation = $this->findOneBy(['token' => $token]);
        if (null === $invitation || !$invitation->isValid()) {
            return null;
        }

        return $invitation;
    }

    public function findLatestPendingForDanseurEmail(int $danseurId, string $email): ?InvitationCoparent
    {
        return $this->createQueryBuilder('i')
            ->andWhere('IDENTITY(i.danseur) = :danseurId')
            ->andWhere('i.email = :email')
            ->andWhere('i.usedAt IS NULL')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('danseurId', $danseurId)
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
