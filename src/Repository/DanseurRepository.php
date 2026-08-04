<?php

namespace App\Repository;

use App\Entity\Danseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Danseur>
 */
class DanseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Danseur::class);
    }

    /**
     * Danseurs accessibles via l'e-mail effectif du 2ᵉ parent
     * (override danseur, sinon fallback foyer), comparaison insensible à la casse.
     *
     * @return list<Danseur>
     */
    public function findAccessibleByParent2Email(string $email): array
    {
        $email = mb_strtolower(trim($email));
        if ('' === $email) {
            return [];
        }

        /** @var list<Danseur> $candidates */
        $candidates = $this->createQueryBuilder('d')
            ->innerJoin('d.foyer', 'f')->addSelect('f')
            ->andWhere('d.parent2Email IS NOT NULL OR f.parent2Email IS NOT NULL')
            ->getQuery()
            ->getResult();

        return array_values(array_filter(
            $candidates,
            static function (Danseur $danseur) use ($email): bool {
                $effectif = $danseur->getParent2EmailEffectif();
                if (null === $effectif || '' === trim($effectif)) {
                    return false;
                }

                return mb_strtolower(trim($effectif)) === $email;
            }
        ));
    }

    /**
     * @return list<Danseur>
     */
    public function findAllForExport(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.foyer', 'f')->addSelect('f')
            ->leftJoin('f.user', 'u')->addSelect('u')
            ->leftJoin('d.cours', 'c')->addSelect('c')
            ->leftJoin('d.inscriptions', 'i')->addSelect('i')
            ->leftJoin('i.cours', 'ic')->addSelect('ic')
            ->orderBy('f.nom', 'ASC')
            ->addOrderBy('d.nom', 'ASC')
            ->addOrderBy('d.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Élèves d'un cours (inscriptions saison + ManyToMany).
     *
     * @return list<Danseur>
     */
    public function findForCoursExport(\App\Entity\Cours $cours, string $saison): array
    {
        /** @var list<Danseur> $viaInscriptions */
        $viaInscriptions = $this->createQueryBuilder('d')
            ->innerJoin('d.inscriptions', 'i')
            ->leftJoin('d.foyer', 'f')->addSelect('f')
            ->leftJoin('f.user', 'u')->addSelect('u')
            ->andWhere('i.cours = :cours')
            ->andWhere('i.saison = :saison')
            ->andWhere('i.estEnListeDAttente = false')
            ->setParameter('cours', $cours)
            ->setParameter('saison', $saison)
            ->orderBy('d.nom', 'ASC')
            ->addOrderBy('d.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        if ($viaInscriptions !== []) {
            return $viaInscriptions;
        }

        return $this->createQueryBuilder('d')
            ->innerJoin('d.cours', 'c')
            ->leftJoin('d.foyer', 'f')->addSelect('f')
            ->leftJoin('f.user', 'u')->addSelect('u')
            ->andWhere('c = :cours')
            ->setParameter('cours', $cours)
            ->orderBy('d.nom', 'ASC')
            ->addOrderBy('d.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
