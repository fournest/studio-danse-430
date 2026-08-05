<?php

namespace App\Repository;

use App\Entity\LdcDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LdcDocument>
 */
class LdcDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LdcDocument::class);
    }

    public function findCurrent(): ?LdcDocument
    {
        return $this->findOneBy(['isCurrent' => true], ['uploadedAt' => 'DESC']);
    }

    public function clearCurrentExcept(?int $exceptId = null): void
    {
        $qb = $this->createQueryBuilder('l')
            ->update()
            ->set('l.isCurrent', ':false')
            ->where('l.isCurrent = :true')
            ->setParameter('false', false)
            ->setParameter('true', true);

        if (null !== $exceptId) {
            $qb->andWhere('l.id != :id')->setParameter('id', $exceptId);
        }

        $qb->getQuery()->execute();
    }
}
