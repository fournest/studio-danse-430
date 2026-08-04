<?php

namespace App\Dto;

/**
 * Ligne d'extra (goodie ou location costume) dans le récapitulatif foyer.
 */
final class CotisationExtraLine
{
    public function __construct(
        public readonly string $label,
        public readonly ?string $taille,
        public readonly int $quantite,
        public readonly float $prixUnitaire,
        public readonly float $prixTotal,
        public readonly string $type,
    ) {
    }
}
