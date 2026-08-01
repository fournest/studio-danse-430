<?php

namespace App\Dto;

/**
 * Ligne tarifaire d'un cours pour un danseur.
 */
final class CotisationCoursLine
{
    public function __construct(
        public readonly string $coursNom,
        public readonly ?int $coursId,
        public readonly float $tarifBrut,
        public readonly bool $isGratuit2020,
        public readonly float $montantApresGratuit,
    ) {
    }
}
