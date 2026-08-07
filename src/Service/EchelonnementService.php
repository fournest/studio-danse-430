<?php

namespace App\Service;

use App\Entity\Inscription;
use App\Entity\Paiement;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement as StatutLignePaiement;

/**
 * Génère les échéances de paiement (chèques ou virements) pour une inscription.
 */
final class EchelonnementService
{
    private const ECHEANCES_SUPPORTES = [1, 3, 10];

    /**
     * Découpe le montant en échéances équitables (arrondi au centime ;
     * le reste éventuel est ajouté à la 1ʳᵉ échéance).
     *
     * @return list<Paiement> objets non encore persistés
     */
    public function generateEcheances(
        Inscription $inscription,
        int $nbEcheances,
        float $montant,
        ?string $emetteur = null,
        ModePaiement $mode = ModePaiement::CHEQUE,
        ?string $libelleVirement = null,
    ): array {
        if (!\in_array($nbEcheances, self::ECHEANCES_SUPPORTES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Nombre d\'échéances non supporté : %d (attendu : 1, 3 ou 10).',
                $nbEcheances
            ));
        }

        if (!\in_array($mode, ModePaiement::modesEchelonnes(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Mode d\'échelonnement non supporté : %s (attendu : chèque ou virement).',
                $mode->value
            ));
        }

        if ($montant < 0) {
            throw new \InvalidArgumentException('Le montant à échelonner ne peut pas être négatif.');
        }

        $montants = $this->repartirMontants($montant, $nbEcheances);
        $dates = $this->genererDatesEncaissement($inscription->getSaison(), $nbEcheances);

        $paiements = [];
        foreach ($montants as $index => $part) {
            $paiement = new Paiement();
            $paiement->setInscription($inscription);
            $paiement->setMontant($part);
            $paiement->setMode($mode);
            $paiement->setStatut(StatutLignePaiement::EN_ATTENTE_REGLEMENT);
            $paiement->setEmetteur($emetteur);
            $paiement->setDateEncaissementPrevue($dates[$index]);

            if ($mode === ModePaiement::VIREMENT) {
                $paiement->setReference($libelleVirement);
                $paiement->setRemarques(sprintf(
                    'Virement %d/%d — libellé : %s',
                    $index + 1,
                    $nbEcheances,
                    $libelleVirement ?? '—'
                ));
            } else {
                $paiement->setReference(sprintf('Chèque %d/%d', $index + 1, $nbEcheances));
            }

            $paiements[] = $paiement;
        }

        return $paiements;
    }

    /**
     * Répartition équitable au centime près.
     *
     * @return list<float>
     */
    public function repartirMontants(float $montant, int $nbEcheances): array
    {
        if ($nbEcheances < 1) {
            throw new \InvalidArgumentException('Le nombre d\'échéances doit être ≥ 1.');
        }

        $montant = round($montant, 2);
        $base = round($montant / $nbEcheances, 2);
        $montants = array_fill(0, $nbEcheances, $base);
        $somme = round($base * $nbEcheances, 2);
        $reste = round($montant - $somme, 2);
        $montants[0] = round($montants[0] + $reste, 2);

        return $montants;
    }

    /**
     * @return list<\DateTimeImmutable>
     */
    public function genererDatesEncaissement(string $saison, int $nbEcheances): array
    {
        $anneeDebut = $this->parseAnneeDebutSaison($saison);

        return match ($nbEcheances) {
            1 => [
                new \DateTimeImmutable(sprintf('%d-10-10', $anneeDebut)),
            ],
            3 => [
                new \DateTimeImmutable(sprintf('%d-10-10', $anneeDebut)),
                new \DateTimeImmutable(sprintf('%d-01-10', $anneeDebut + 1)),
                new \DateTimeImmutable(sprintf('%d-04-10', $anneeDebut + 1)),
            ],
            10 => array_map(
                static function (int $mois) use ($anneeDebut): \DateTimeImmutable {
                    $annee = $mois >= 10 ? $anneeDebut : $anneeDebut + 1;

                    return new \DateTimeImmutable(sprintf('%d-%02d-10', $annee, $mois));
                },
                [10, 11, 12, 1, 2, 3, 4, 5, 6, 7]
            ),
            default => throw new \InvalidArgumentException(sprintf(
                'Nombre d\'échéances non supporté : %d.',
                $nbEcheances
            )),
        };
    }

    private function parseAnneeDebutSaison(string $saison): int
    {
        if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim($saison), $m)) {
            return (int) $m[1];
        }

        throw new \InvalidArgumentException(sprintf(
            'Format de saison invalide : "%s" (attendu YYYY/YYYY).',
            $saison
        ));
    }
}
