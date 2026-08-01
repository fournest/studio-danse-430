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
}
