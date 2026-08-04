<?php

namespace App\Repository;

use App\Entity\CommandeBoutiqueLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeBoutiqueLigne>
 */
class CommandeBoutiqueLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeBoutiqueLigne::class);
    }
}
