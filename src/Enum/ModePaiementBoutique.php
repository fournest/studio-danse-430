<?php

namespace App\Enum;

enum ModePaiementBoutique: string
{
    case HELLOASSO = 'helloasso';
    case CHEQUE = 'cheque';
    case ESPECES = 'especes';

    public function getLabel(): string
    {
        return match ($this) {
            self::HELLOASSO => 'Paiement en ligne (HelloAsso / CB)',
            self::CHEQUE => 'Chèque au club',
            self::ESPECES => 'Espèces au club',
        };
    }
}
