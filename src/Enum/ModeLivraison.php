<?php

namespace App\Enum;

enum ModeLivraison: string
{
    case RETRAIT_LOCAUX = 'retrait_locaux';
    case POINT_RELAIS = 'point_relais';

    public function getLabel(): string
    {
        return match ($this) {
            self::RETRAIT_LOCAUX => 'Retrait aux locaux à Nieul-le-Dolent (Gratuit)',
            self::POINT_RELAIS => 'Livraison en point relais (sur devis)',
        };
    }
}