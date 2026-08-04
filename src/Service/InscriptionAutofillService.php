<?php

namespace App\Service;

use App\Dto\CotisationDetail;
use App\Entity\Foyer;
use App\Entity\Inscription;

/**
 * Remplit / recalcule automatiquement les champs dérivés d'une Inscription
 * (montant total foyer, sync santé) pour le tunnel foyer et EasyAdmin.
 */
final class InscriptionAutofillService
{
    public function __construct(
        private readonly CotisationCalculatorService $calculator,
    ) {
    }

    public function syncSante(Inscription $inscription): void
    {
        $inscription->syncCertificatMedicalFromDanseur();
    }

    public function syncSanteFoyer(Foyer $foyer, string $saison): void
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison) {
                    $inscription->syncCertificatMedicalFromDanseur();
                }
            }
        }
    }

    /**
     * Recalcule la cotisation foyer et réécrit montantTotal sur les inscriptions.
     * Si un plan de paiement existe déjà, conserve le modèle « total concentré »
     * sur l'inscription porteuse ; sinon répartition pro-rata de la cotisation cours.
     */
    public function recalculateMontantsForFoyer(Foyer $foyer, string $saison): CotisationDetail
    {
        $detail = $this->calculator->calculateForFoyer($foyer, $saison);
        $porteuse = $this->findInscriptionPorteuseReglement($foyer, $saison);

        if (null !== $porteuse) {
            $this->concentrerGrandTotal($foyer, $saison, $porteuse, $detail);
        } else {
            $this->repartirProRataCotisation($foyer, $saison, $detail);
        }

        return $detail;
    }

    /**
     * Finalise la soumission foyer : montants à jour, sync santé, statut EN_ATTENTE_VALIDATION.
     *
     * @return list<Inscription>
     */
    public function finaliserSoumissionFoyer(Foyer $foyer, string $saison): array
    {
        $this->recalculateMontantsForFoyer($foyer, $saison);
        $this->syncSanteFoyer($foyer, $saison);

        $soumises = [];
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if ($inscription->getStatut() === \App\Enum\StatutInscription::BROUILLON) {
                    $inscription->soumettreAuBureau();
                    $soumises[] = $inscription;
                }
            }
        }

        return $soumises;
    }

    private function findInscriptionPorteuseReglement(Foyer $foyer, string $saison): ?Inscription
    {
        $avecPaiements = null;
        $avecMontant = null;

        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if (!$inscription->getPaiements()->isEmpty()) {
                    return $inscription;
                }
                if (null === $avecMontant && ($inscription->getMontantTotal() ?? 0.0) > 0.009) {
                    $avecMontant = $inscription;
                }
                if (null === $avecPaiements) {
                    $avecPaiements = $inscription;
                }
            }
        }

        return $avecMontant;
    }

    private function concentrerGrandTotal(
        Foyer $foyer,
        string $saison,
        Inscription $cible,
        CotisationDetail $detail,
    ): void {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if ($inscription === $cible || $inscription->getId() === $cible->getId()) {
                    $inscription->setMontantTotal($detail->grandTotal);
                } else {
                    $inscription->setMontantTotal(0);
                }
                $inscription->refreshStatutPaiement();
            }
        }
    }

    private function repartirProRataCotisation(Foyer $foyer, string $saison, CotisationDetail $detail): void
    {
        $entries = [];
        $poidsTotal = 0.0;

        foreach ($detail->breakdownByDanseur as $block) {
            foreach ($foyer->getDanseurs() as $danseur) {
                if ($danseur->getId() !== $block->danseurId) {
                    continue;
                }
                foreach ($block->lines as $line) {
                    foreach ($danseur->getInscriptions() as $inscription) {
                        if ($inscription->getSaison() !== $saison || $inscription->getCours()?->getId() !== $line->coursId) {
                            continue;
                        }
                        $poids = $line->isListeAttente ? 0.0 : $line->montantApresGratuit;
                        $entries[] = ['inscription' => $inscription, 'poids' => $poids];
                        $poidsTotal += $poids;
                    }
                }
            }
        }

        if ($entries === []) {
            return;
        }

        $reste = $detail->total;
        $lastIndex = \count($entries) - 1;

        foreach ($entries as $i => $entry) {
            if ($poidsTotal <= 0.0) {
                $montant = 0.0;
            } elseif ($i === $lastIndex) {
                $montant = round(max(0.0, $reste), 2);
            } else {
                $montant = round($detail->total * ($entry['poids'] / $poidsTotal), 2);
                $reste = round($reste - $montant, 2);
            }
            $entry['inscription']->setMontantTotal($montant);
            $entry['inscription']->refreshStatutPaiement();
        }
    }
}
