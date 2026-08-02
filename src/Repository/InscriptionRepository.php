<?php

namespace App\Repository;

use App\Entity\Cours;
use App\Entity\Inscription;
use App\Enum\StatutInscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Retourne les inscriptions d'une saison donnée.
     *
     * @return Inscription[]
     */
    public function findBySaison(string $saison): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.saison = :saison')
            ->setParameter('saison', $saison)
            ->getQuery()
            ->getResult();
    }

    /**
     * Places occupées (hors liste d'attente / annulées), pour contrôle de capacité.
     *
     * @param list<int> $excludeInscriptionIds
     */
    public function countOccupants(Cours $cours, string $saison, array $excludeInscriptionIds = []): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.cours = :cours')
            ->andWhere('i.saison = :saison')
            ->andWhere('i.estEnListeDAttente = false')
            ->andWhere('i.statut IN (:statuts)')
            ->setParameter('cours', $cours)
            ->setParameter('saison', $saison)
            ->setParameter('statuts', [
                StatutInscription::BROUILLON,
                StatutInscription::EN_ATTENTE_VALIDATION,
                StatutInscription::VALIDE,
            ]);

        if ($excludeInscriptionIds !== []) {
            $qb->andWhere('i.id NOT IN (:excludeIds)')
                ->setParameter('excludeIds', $excludeInscriptionIds);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Inscription>
     */
    public function findListeAttenteByCours(Cours $cours, string $saison): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.danseur', 'd')->addSelect('d')
            ->andWhere('i.cours = :cours')
            ->andWhere('i.saison = :saison')
            ->andWhere('i.estEnListeDAttente = true')
            ->andWhere('i.statut != :annule')
            ->setParameter('cours', $cours)
            ->setParameter('saison', $saison)
            ->setParameter('annule', StatutInscription::ANNULE)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Dernières inscriptions dont le danseur et le cours existent encore.
     *
     * @return list<Inscription>
     */
    public function findRecentWithRelations(int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.danseur', 'd')->addSelect('d')
            ->innerJoin('i.cours', 'c')->addSelect('c')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Inscriptions pointant vers un danseur ou un cours supprimé.
     *
     * @return list<Inscription>
     */
    public function findOrphans(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.danseur', 'd')
            ->leftJoin('i.cours', 'c')
            ->andWhere('d.id IS NULL OR c.id IS NULL')
            ->getQuery()
            ->getResult();
    }
}
