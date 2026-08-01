<?php

namespace App\Dto;

/**
 * Détail cotisation pour un danseur du foyer.
 */
final class CotisationDanseurBreakdown
{
    /**
     * @param list<CotisationCoursLine> $lines
     */
    public function __construct(
        public readonly string $danseurNom,
        public readonly ?int $danseurId,
        public readonly ?int $anneeNaissance,
        public readonly array $lines,
    ) {
    }

    public function getSubtotal(): float
    {
        return array_reduce(
            $this->lines,
            static fn (float $sum, CotisationCoursLine $line): float => $sum + $line->tarifBrut,
            0.0
        );
    }
}
