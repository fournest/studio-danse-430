<?php

namespace App\Enum;

enum StatutCommandeBoutique: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRMEE = 'confirmee';
    case PAYEE = 'payee';
    case ANNULEE = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de règlement',
            self::CONFIRMEE => 'Confirmée',
            self::PAYEE => 'Payée',
            self::ANNULEE => 'Annulée',
        };
    }
}
