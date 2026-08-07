<?php

namespace App\Enum;

enum StatutCommandeBoutique: string
{
    case EN_ATTENTE_REGLEMENT = 'en_attente';
    case CONFIRMEE = 'confirmee';
    case PAYE = 'payee';
    case ANNULE = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE_REGLEMENT => 'Paiement en attente',
            self::CONFIRMEE => 'Confirmée',
            self::PAYE => 'Payé',
            self::ANNULE => 'Annulé',
        };
    }
}
