<?php

namespace App\Service;

use App\Dto\CotisationCoursLine;
use App\Dto\CotisationDanseurBreakdown;
use App\Dto\CotisationDetail;
use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Foyer;
use App\Entity\Inscription;

/**
 * Calcule les cotisations saison 2026-2027 selon les règles tarifaires du Studio Danse 430.
 * Boutique et locations costumes sont facturées dans leurs tunnels dédiés.
 *
 * Remise dégressive foyer (2 cours → −20 %, 3+ → −30 %) :
 * STRICTEMENT calculée sur les inscriptions du foyer payeur
 * ({@see Foyer::getInscriptions()}). Un cours vu en lecture seule par un
 * co-parent (enfant rattaché à un autre foyer) ne compte pas.
 */
final class CotisationCalculatorService
{
    public const SAISON_COURANTE = '2026/2027';
    public const ANNEE_GRATUITE = 2020;

    public function __construct()
    {
    }

    /**
     * Calcule la cotisation du foyer payeur uniquement.
     * Source de vérité : inscriptions des danseurs de ce foyer (pas les rattachements co-parent).
     */
    public function calculateForFoyer(Foyer $foyer, string $saison = self::SAISON_COURANTE): CotisationDetail
    {
        $selection = $this->buildSelectionFromFoyerPayeur($foyer, $saison);

        return $this->calculate($selection, $foyer, $saison);
    }

    /**
     * Alias métier : montant total dû par le foyer pour la saison (dégressivité + extras).
     */
    public function calculerTotalFoyer(Foyer $foyer, string $saison = self::SAISON_COURANTE): CotisationDetail
    {
        return $this->calculateForFoyer($foyer, $saison);
    }

    /**
     * Calcule depuis une sélection libre (ex. formulaire avant persistance).
     * Si un foyer payeur est fourni, les danseurs hors de ce foyer sont exclus.
     *
     * @param list<array{danseur: Danseur, cours: list<Cours>, attenteIds?: list<int>}> $selection
     */
    public function calculate(
        array $selection,
        ?Foyer $foyer = null,
        string $saison = self::SAISON_COURANTE,
    ): CotisationDetail {
        if (null !== $foyer) {
            $selection = array_values(array_filter(
                $selection,
                fn (array $entry): bool => $this->danseurBelongsToPayingFoyer($entry['danseur'], $foyer)
            ));
        }

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
            /** @var list<int> $attenteIds */
            $attenteIds = array_map('intval', $entry['attenteIds'] ?? []);

            if ($coursList === []) {
                continue;
            }

            $rawLines = [];
            foreach ($coursList as $cours) {
                $isAttente = \in_array((int) $cours->getId(), $attenteIds, true);
                $tarif = $isAttente ? 0.0 : $this->resolveTarif($cours);
                if (!$isAttente) {
                    $subtotal += $tarif;
                }
                $rawLines[] = [
                    'cours' => $cours,
                    'tarif' => $isAttente ? $this->resolveTarif($cours) : $tarif,
                    'isGratuit2020' => false,
                    'isListeAttente' => $isAttente,
                ];
            }

            $payingLines = array_values(array_filter(
                $rawLines,
                static fn (array $line): bool => !$line['isListeAttente']
            ));

            $anneeNaissance = $danseur->getAnneeNaissance();
            if ($anneeNaissance === self::ANNEE_GRATUITE && count($payingLines) >= 2) {
                $cheapestIndex = $this->findCheapestIndex($payingLines);
                $cheapestCoursId = $payingLines[$cheapestIndex]['cours']->getId();
                $gratuit2020Amount += $payingLines[$cheapestIndex]['tarif'];
                foreach ($rawLines as $idx => $raw) {
                    if (!$raw['isListeAttente'] && $raw['cours']->getId() === $cheapestCoursId) {
                        $rawLines[$idx]['isGratuit2020'] = true;
                        break;
                    }
                }
            }

            $lines = [];
            foreach ($rawLines as $raw) {
                if ($raw['isListeAttente']) {
                    $lines[] = new CotisationCoursLine(
                        coursNom: $raw['cours']->getNomComplet(),
                        coursId: $raw['cours']->getId(),
                        tarifBrut: $this->roundMoney($raw['tarif']),
                        isGratuit2020: false,
                        montantApresGratuit: 0.0,
                        isListeAttente: true,
                    );
                    continue;
                }

                $montantApres = $raw['isGratuit2020'] ? 0.0 : $raw['tarif'];
                if (!$raw['isGratuit2020']) {
                    $payingAmounts[] = $montantApres;
                }

                /** @var Cours $coursEntity */
                $coursEntity = $raw['cours'];
                $lines[] = new CotisationCoursLine(
                    coursNom: $coursEntity->getNomComplet(),
                    coursId: $coursEntity->getId(),
                    tarifBrut: $this->roundMoney($raw['tarif']),
                    isGratuit2020: $raw['isGratuit2020'],
                    montantApresGratuit: $this->roundMoney($montantApres),
                    isListeAttente: false,
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
        $totalCotisation = $this->roundMoney(max(0.0, $totalAvantRemiseManuelle - $remiseManuelleAmount));

        [$goodiesAmount, $goodiesLines, $costumesAmount, $costumesLines] = $this->resolveExtras($foyer, $saison);
        $grandTotal = $this->roundMoney($totalCotisation + $goodiesAmount + $costumesAmount);

        return new CotisationDetail(
            subtotal: $this->roundMoney($subtotal),
            gratuit2020Amount: $this->roundMoney($gratuit2020Amount),
            foyerDiscountPercentage: $foyerDiscountPercentage,
            foyerDiscountAmount: $foyerDiscountAmount,
            remiseManuelleAmount: $this->roundMoney($remiseManuelleAmount),
            motifRemise: $motifRemise,
            total: $totalCotisation,
            payingCoursesCount: $payingCoursesCount,
            breakdownByDanseur: $breakdownByDanseur,
            goodiesAmount: $goodiesAmount,
            costumesAmount: $costumesAmount,
            goodiesLines: $goodiesLines,
            costumesLines: $costumesLines,
            grandTotal: $grandTotal,
        );
    }

    /**
     * Construit la sélection tarifaire STRICTEMENT depuis les inscriptions du foyer payeur.
     *
     * @return list<array{danseur: Danseur, cours: list<Cours>, attenteIds: list<int>}>
     */
    private function buildSelectionFromFoyerPayeur(Foyer $foyer, string $saison): array
    {
        /** @var array<int|string, array{danseur: Danseur, cours: array<int|string, Cours>, attenteIds: list<int>}> $byDanseur */
        $byDanseur = [];

        foreach ($foyer->getInscriptions($saison) as $inscription) {
            if (!$this->inscriptionBelongsToPayingFoyer($inscription, $foyer)) {
                continue;
            }

            $danseur = $inscription->getDanseur();
            if (null === $danseur || !$this->danseurBelongsToPayingFoyer($danseur, $foyer)) {
                continue;
            }

            $cours = $inscription->getCours();
            if (null === $cours) {
                continue;
            }

            $danseurKey = $danseur->getId() ?? spl_object_id($danseur);
            if (!isset($byDanseur[$danseurKey])) {
                $byDanseur[$danseurKey] = [
                    'danseur' => $danseur,
                    'cours' => [],
                    'attenteIds' => [],
                ];
            }

            $coursKey = $cours->getId() ?? spl_object_id($cours);
            $byDanseur[$danseurKey]['cours'][$coursKey] = $cours;

            if ($inscription->isEstEnListeDAttente() && null !== $cours->getId()) {
                $byDanseur[$danseurKey]['attenteIds'][] = (int) $cours->getId();
            }
        }

        if ($byDanseur !== []) {
            $selection = [];
            foreach ($byDanseur as $entry) {
                $selection[] = [
                    'danseur' => $entry['danseur'],
                    'cours' => array_values($entry['cours']),
                    'attenteIds' => array_values(array_unique($entry['attenteIds'])),
                ];
            }

            return $selection;
        }

        // Fallback tests / avant persistance des Inscription : ManyToMany des danseurs DU foyer payeur uniquement.
        $selection = [];
        foreach ($foyer->getDanseurs() as $danseur) {
            if (!$this->danseurBelongsToPayingFoyer($danseur, $foyer)) {
                continue;
            }
            $coursList = $danseur->getCours()->toArray();
            if ($coursList === []) {
                continue;
            }
            $selection[] = [
                'danseur' => $danseur,
                'cours' => array_values($coursList),
                'attenteIds' => [],
            ];
        }

        return $selection;
    }

    private function inscriptionBelongsToPayingFoyer(Inscription $inscription, Foyer $foyer): bool
    {
        $danseur = $inscription->getDanseur();

        return null !== $danseur && $this->danseurBelongsToPayingFoyer($danseur, $foyer);
    }

    /**
     * Un danseur ne contribue à la remise foyer que s'il appartient au foyer payeur.
     * Les enfants seulement visibles en co-parent (autre foyer) sont exclus.
     */
    private function danseurBelongsToPayingFoyer(Danseur $danseur, Foyer $foyer): bool
    {
        $danseurFoyer = $danseur->getFoyer();
        if (null === $danseurFoyer) {
            return false;
        }

        if ($danseurFoyer === $foyer) {
            return true;
        }

        $foyerId = $foyer->getId();
        $danseurFoyerId = $danseurFoyer->getId();

        return null !== $foyerId
            && null !== $danseurFoyerId
            && $foyerId === $danseurFoyerId;
    }

    /**
     * @return array{0: float, 1: list<CotisationExtraLine>, 2: float, 3: list<CotisationExtraLine>}
     */
    private function resolveExtras(?Foyer $foyer, string $saison): array
    {
        // Boutique & costumes ont leurs propres tunnels de paiement :
        // ils ne sont plus inclus dans le règlement foyer.
        unset($foyer, $saison);

        return [0.0, [], 0.0, []];
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

        // Remises manuelles : uniquement sur les inscriptions du foyer payeur.
        foreach ($foyer->getInscriptions($saison) as $inscription) {
            if (!$this->inscriptionBelongsToPayingFoyer($inscription, $foyer)) {
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

        $motif = $motifs === [] ? null : implode(' · ', array_unique($motifs));

        return [$amount, $motif];
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
