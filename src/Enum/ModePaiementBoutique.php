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
            self::CHEQUE => 'Chèque (remise sous enveloppe au club)',
            self::ESPECES => 'Espèces (remise sous enveloppe au club)',
        };
    }

    public function isPaiementClub(): bool
    {
        return $this === self::CHEQUE || $this === self::ESPECES;
    }
}
