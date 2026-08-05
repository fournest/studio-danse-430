<?php

namespace App\Repository;

use App\Entity\Foyer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Foyer>
 */
class FoyerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Foyer::class);
    }

    /**
     * Recherche un autre foyer partageant la même adresse postale et le même code postal
     * (comparaison insensible à la casse et aux espaces).
     */
    public function findOtherFoyerByAdresse(string $adresse, string $codePostal, Foyer $currentFoyer): ?Foyer
    {
        $adresseNorm = self::normalizeAdresse($adresse);
        $cpNorm = self::normalizeAdresse($codePostal);

        if ($adresseNorm === '' || $cpNorm === '') {
            return null;
        }

        $qb = $this->createQueryBuilder('f')
            ->innerJoin('f.user', 'u')
            ->addSelect('u');

        if (null !== $currentFoyer->getId()) {
            $qb->andWhere('f.id != :currentId')
                ->setParameter('currentId', $currentFoyer->getId());
        }

        /** @var list<Foyer> $candidats */
        $candidats = $qb->getQuery()->getResult();

        foreach ($candidats as $foyer) {
            if (self::normalizeAdresse((string) $foyer->getAdresse()) === $adresseNorm
                && self::normalizeAdresse((string) $foyer->getCodePostal()) === $cpNorm
            ) {
                return $foyer;
            }
        }

        return null;
    }

    public static function normalizeAdresse(string $value): string
    {
        $value = mb_strtolower(trim($value));
        // Retire espaces, ponctuation courante et tirets pour comparer « 12 rue X » ≈ « 12  Rue X ».
        $value = preg_replace('/[\s\-_.,\'’]+/u', '', $value) ?? $value;

        return $value;
    }
}
