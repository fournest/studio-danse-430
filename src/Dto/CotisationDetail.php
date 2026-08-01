<?php

namespace App\Dto;

/**
 * Résultat structuré du calcul de cotisation d'un foyer (saison 2026-2027).
 */
final class CotisationDetail
{
    /**
     * @param list<CotisationDanseurBreakdown> $breakdownByDanseur
     */
    public function __construct(
        public readonly float $subtotal,
        public readonly float $gratuit2020Amount,
        public readonly int $foyerDiscountPercentage,
        public readonly float $foyerDiscountAmount,
        public readonly float $remiseManuelleAmount,
        public readonly ?string $motifRemise,
        public readonly float $total,
        public readonly int $payingCoursesCount,
        public readonly array $breakdownByDanseur,
    ) {
    }
}
