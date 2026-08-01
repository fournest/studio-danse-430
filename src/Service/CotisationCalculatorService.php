<?php

namespace App\Service;

use App\Dto\CotisationCoursLine;
use App\Dto\CotisationDanseurBreakdown;
use App\Dto\CotisationDetail;
use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Foyer;

/**
 * Calcule les cotisations saison 2026-2027 selon les règles tarifaires du Studio Danse 430.
 *
 * Ordre de calcul :
 * 1. Tarifs bruts des cours sélectionnés (champ Cours::$tarif)
 * 2. Gratuité du 2ᵉ cours (le moins cher) pour les enfants nés en 2020
 * 3. Remise dégressive foyer sur les cours restants payants (−20 % / −30 %)
 * 4. Remise manuelle bureau (Foyer + Inscriptions)
 */
final class CotisationCalculatorService
{
    public const SAISON_COURANTE = '2026/2027';
    public const ANNEE_GRATUITE = 2020;

    /**
     * Calcule la cotisation à partir des inscriptions (ou des cours ManyToMany) du foyer.
     */
    public function calculateForFoyer(Foyer $foyer, string $saison = self::SAISON_COURANTE): CotisationDetail
    {
        $selection = [];

        foreach ($foyer->getDanseurs() as $danseur) {
            $selection[] = [
                'danseur' => $danseur,
                'cours' => $this->resolveCoursForDanseur($danseur, $saison),
            ];
        }

        return $this->calculate($selection, $foyer, $saison);
    }

    /**
     * Calcule depuis une sélection libre (ex. formulaire avant persistance).
     *
     * @param list<array{danseur: Danseur, cours: list<Cours>}> $selection
     */
    public function calculate(
        array $selection,
        ?Foyer $foyer = null,
        string $saison = self::SAISON_COURANTE,
    ): CotisationDetail {
        $breakdownByDanseur = [];
        $subtotal = 0.0;
        $gratuit2020Amount = 0.0;
        /** @var list<float> $payingAmounts montants restants après gratuité 2020 */
        $payingAmounts = [];

        foreach ($selection as $entry) {
            /** @var Danseur $danseur */
            $danseur = $entry['danseur'];
            /** @var list<Cours> $coursList */
            $coursList = array_values($entry['cours'] ?? []);

            if ($coursList === []) {
                continue;
            }

            $rawLines = [];
            foreach ($coursList as $cours) {
                $tarif = $this->resolveTarif($cours);
                $subtotal += $tarif;
                $rawLines[] = [
                    'cours' => $cours,
                    'tarif' => $tarif,
                    'isGratuit2020' => false,
                ];
            }

            $anneeNaissance = $danseur->getAnneeNaissance();
            if ($anneeNaissance === self::ANNEE_GRATUITE && count($rawLines) >= 2) {
                $cheapestIndex = $this->findCheapestIndex($rawLines);
                $gratuit2020Amount += $rawLines[$cheapestIndex]['tarif'];
                $rawLines[$cheapestIndex]['isGratuit2020'] = true;
            }

            $lines = [];
            foreach ($rawLines as $raw) {
                $montantApres = $raw['isGratuit2020'] ? 0.0 : $raw['tarif'];
                if (!$raw['isGratuit2020']) {
                    $payingAmounts[] = $montantApres;
                }

                /** @var Cours $coursEntity */
                $coursEntity = $raw['cours'];
                $lines[] = new CotisationCoursLine(
                    coursNom: $coursEntity->getNom(),
                    coursId: $coursEntity->getId(),
                    tarifBrut: $this->roundMoney($raw['tarif']),
                    isGratuit2020: $raw['isGratuit2020'],
                    montantApresGratuit: $this->roundMoney($montantApres),
                );
            }

            $breakdownByDanseur[] = new CotisationDanseurBreakdown(
                danseurNom: (string) $danseur,
                danseurId: $danseur->getId(),
                anneeNaissance: $anneeNaissance,
                lines: $lines,
            );
        }

        $payingCoursesCount = count($payingAmounts);
        $payingSubtotal = array_sum($payingAmounts);
        $foyerDiscountPercentage = $this->resolveFoyerDiscountPercentage($payingCoursesCount);
        $foyerDiscountAmount = $this->roundMoney($payingSubtotal * ($foyerDiscountPercentage / 100));
        $totalAvantRemiseManuelle = $this->roundMoney($payingSubtotal - $foyerDiscountAmount);

        [$remiseManuelleAmount, $motifRemise] = $this->resolveRemiseManuelle($foyer, $saison);
        $total = $this->roundMoney(max(0.0, $totalAvantRemiseManuelle - $remiseManuelleAmount));

        return new CotisationDetail(
            subtotal: $this->roundMoney($subtotal),
            gratuit2020Amount: $this->roundMoney($gratuit2020Amount),
            foyerDiscountPercentage: $foyerDiscountPercentage,
            foyerDiscountAmount: $foyerDiscountAmount,
            remiseManuelleAmount: $this->roundMoney($remiseManuelleAmount),
            motifRemise: $motifRemise,
            total: $total,
            payingCoursesCount: $payingCoursesCount,
            breakdownByDanseur: $breakdownByDanseur,
        );
    }

    /**
     * @return array{0: float, 1: ?string}
     */
    private function resolveRemiseManuelle(?Foyer $foyer, string $saison): array
    {
        if (null === $foyer) {
            return [0.0, null];
        }

        $amount = 0.0;
        $motifs = [];

        if (null !== $foyer->getRemiseManuelle() && $foyer->getRemiseManuelle() > 0) {
            $amount += $foyer->getRemiseManuelle();
            if ($foyer->getMotifRemise()) {
                $motifs[] = $foyer->getMotifRemise();
            }
        }

        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                $remise = $inscription->getRemiseManuelle();
                if (null === $remise || $remise <= 0) {
                    continue;
                }
                $amount += $remise;
                if ($inscription->getMotifRemise()) {
                    $motifs[] = $inscription->getMotifRemise();
                }
            }
        }

        $motif = $motifs === [] ? null : implode(' · ', array_unique($motifs));

        return [$amount, $motif];
    }

    /**
     * @return list<Cours>
     */
    private function resolveCoursForDanseur(Danseur $danseur, string $saison): array
    {
        $byKey = [];

        foreach ($danseur->getInscriptions() as $inscription) {
            if ($inscription->getSaison() !== $saison) {
                continue;
            }
            $cours = $inscription->getCours();
            if (null === $cours) {
                continue;
            }
            $key = $cours->getId() ?? spl_object_id($cours);
            $byKey[$key] = $cours;
        }

        // Repli : sélection ManyToMany (tunnel / admin) si aucune inscription saison
        if ($byKey === []) {
            foreach ($danseur->getCours() as $cours) {
                $key = $cours->getId() ?? spl_object_id($cours);
                $byKey[$key] = $cours;
            }
        }

        return array_values($byKey);
    }

    private function resolveTarif(Cours $cours): float
    {
        return (float) $cours->getTarif();
    }

    /**
     * @param list<array{cours: Cours, tarif: float, isGratuit2020: bool}> $rawLines
     */
    private function findCheapestIndex(array $rawLines): int
    {
        $cheapestIndex = 0;
        $cheapestTarif = $rawLines[0]['tarif'];

        foreach ($rawLines as $index => $line) {
            if ($line['tarif'] < $cheapestTarif) {
                $cheapestTarif = $line['tarif'];
                $cheapestIndex = $index;
            }
        }

        return $cheapestIndex;
    }

    private function resolveFoyerDiscountPercentage(int $payingCoursesCount): int
    {
        if ($payingCoursesCount >= 3) {
            return 30;
        }
        if ($payingCoursesCount === 2) {
            return 20;
        }

        return 0;
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
