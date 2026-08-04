<?php

namespace App\Enum;

enum ModeRetraitBoutique: string
{
    case RETRAIT_CLUB = 'retrait_club';
    case LIVRAISON = 'livraison';

    public function getLabel(): string
    {
        return match ($this) {
            self::RETRAIT_CLUB => 'Retrait au studio',
            self::LIVRAISON => 'Livraison à domicile',
        };
    }
}
