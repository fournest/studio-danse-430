<?php

namespace App\Enum;

enum StatutReservation: string
{
    case EN_ATTENTE = 'En attente';
    case VALIDEE = 'Validée';
    case REFUSEE = 'Refusée';
    case RESTITUEE = 'Restituée';
    case ANNULEE = 'Annulée';

    public function getLabel(): string
    {
        return $this->value;
    }
}