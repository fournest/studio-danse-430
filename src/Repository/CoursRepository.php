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
     * Retourne les cours triés par jour de la semaine (Lun→Dim) puis par heure.
     *
     * @return Cours[]
     */
    public function findAllOrdered(): array
    {
        $cours = $this->createQueryBuilder('c')
            ->addOrderBy('c.heure', 'ASC')
            ->getQuery()
            ->getResult();

        $order = [
            'lundi' => 1,
            'mardi' => 2,
            'mercredi' => 3,
            'jeudi' => 4,
            'vendredi' => 5,
            'samedi' => 6,
            'dimanche' => 7,
        ];

        usort($cours, static function (Cours $a, Cours $b) use ($order): int {
            $da = $order[mb_strtolower(trim($a->getJour()))] ?? 99;
            $db = $order[mb_strtolower(trim($b->getJour()))] ?? 99;
            if ($da !== $db) {
                return $da <=> $db;
            }

            return $a->getHeure() <=> $b->getHeure();
        });

        return $cours;
    }

    /**
     * Cours groupés par jour (Lundi → Dimanche), triés par heure de début.
     * Une seule clé par jour (normalisée) pour éviter les colonnes / en-têtes dupliqués.
     *
     * @return array<string, list<Cours>>
     */
    public function findGroupedByJour(): array
    {
        $order = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $grouped = array_fill_keys($order, []);
        $aliases = [];
        foreach ($order as $jour) {
            $aliases[mb_strtolower($jour)] = $jour;
        }

        foreach ($this->findAllOrdered() as $cours) {
            $raw = trim($cours->getJour());
            $key = $aliases[mb_strtolower($raw)] ?? null;
            if (null === $key) {
                // Jour hors grille standard : colonne dédiée en fin de liste
                if (!\array_key_exists($raw, $grouped)) {
                    $grouped[$raw] = [];
                }
                $key = $raw;
            }
            $grouped[$key][] = $cours;
        }

        // Ne garder que Lundi→Dimanche dans l’ordre (les colonnes vides restent pour la grille 7 jours)
        $ordered = [];
        foreach ($order as $jour) {
            $ordered[$jour] = $grouped[$jour];
        }

        return $ordered;
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
     * Cours éligibles selon l'âge et/ou l'année de naissance du danseur.
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

    /**
     * @return Cours[]
     */
    public function findEligibleForDanseur(\App\Entity\Danseur $danseur): array
    {
        $eligible = [];
        foreach ($this->findAllOrdered() as $cours) {
            if ($cours->isEligibleForDanseur($danseur)) {
                $eligible[] = $cours;
            }
        }

        return $eligible;
    }
}
