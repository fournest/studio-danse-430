<?php

namespace App\Dto;

/**
 * Résultat structuré du calcul de cotisation d'un foyer (saison 2026-2027).
 *
 * - `total` : cotisation cours (après gratuités / remises)
 * - `grandTotal` : cotisation + goodies + locations costumes
 */
final class CotisationDetail
{
    /**
     * @param list<CotisationDanseurBreakdown> $breakdownByDanseur
     * @param list<CotisationExtraLine> $goodiesLines
     * @param list<CotisationExtraLine> $costumesLines
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
        public readonly float $goodiesAmount = 0.0,
        public readonly float $costumesAmount = 0.0,
        public readonly array $goodiesLines = [],
        public readonly array $costumesLines = [],
        public readonly float $grandTotal = 0.0,
    ) {
    }

    public function getExtrasAmount(): float
    {
        return round($this->goodiesAmount + $this->costumesAmount, 2);
    }
}
