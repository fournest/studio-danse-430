<?php

namespace App\Enum;

enum ModePaiement: string
{
    case CHEQUE = 'cheque';
    case ANCV = 'ancv';
    case PASS_SPORT = 'pass_sport';
    case VIREMENT = 'virement';
    case ESPECES = 'especes';
    case HELLOASSO = 'helloasso';

    public function getLabel(): string
    {
        return match ($this) {
            self::CHEQUE => 'Chèque',
            self::ANCV => 'Chèque ANCV (Vacances)',
            self::PASS_SPORT => 'Pass\'Sport',
            self::VIREMENT => 'Virement',
            self::ESPECES => 'Espèces',
            self::HELLOASSO => 'Paiement en ligne (HelloAsso)',
        };
    }
}
