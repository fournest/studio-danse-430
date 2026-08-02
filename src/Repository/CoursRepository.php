<?php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * Retourne les cours triés par jour puis par heure.
     *
     * @return Cours[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.jour', 'ASC')
            ->addOrderBy('c.heure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cours groupés par jour (ordre club : Lun → Sam, hors jeudi).
     *
     * @return array<string, list<Cours>>
     */
    public function findGroupedByJour(): array
    {
        $order = ['Lundi', 'Mardi', 'Mercredi', 'Vendredi', 'Samedi'];
        $grouped = array_fill_keys($order, []);

        foreach ($this->findAllOrdered() as $cours) {
            $jour = $cours->getJour();
            if (!\array_key_exists($jour, $grouped)) {
                $grouped[$jour] = [];
            }
            $grouped[$jour][] = $cours;
        }

        return array_filter($grouped, static fn (array $items): bool => $items !== []);
    }

    /**
     * Cours attribués à un professeur (nom ou email legacy dans le champ libre).
     *
     * @return Cours[]
     */
    public function findForProfesseur(?string $email, ?string $nomAffiche = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.jour', 'ASC')
            ->addOrderBy('c.heure', 'ASC');

        $ors = [];
        if ($email) {
            $ors[] = 'c.professeur = :email';
            $ors[] = 'c.professeur LIKE :emailContains';
            $qb->setParameter('email', $email)
                ->setParameter('emailContains', '%'.$email.'%');
        }
        if ($nomAffiche) {
            $ors[] = 'LOWER(c.professeur) LIKE :nom';
            $qb->setParameter('nom', '%'.mb_strtolower($nomAffiche).'%');
        }

        if ($ors === []) {
            return [];
        }

        $qb->andWhere(implode(' OR ', $ors));

        return $qb->getQuery()->getResult();
    }

    /**
     * Cours éligibles selon l'année de naissance du danseur.
     *
     * @return Cours[]
     */
    public function findEligibleForBirthYear(?int $anneeNaissance): array
    {
        if (null === $anneeNaissance) {
            return $this->findAllOrdered();
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.anneeNaissanceMin IS NULL OR c.anneeNaissanceMin <= :annee')
            ->andWhere('c.anneeNaissanceMax IS NULL OR c.anneeNaissanceMax >= :annee')
            ->setParameter('annee', $anneeNaissance)
            ->orderBy('c.jour', 'ASC')
            ->addOrderBy('c.heure', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
