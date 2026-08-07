<?php

namespace App\Enum;

enum SeatingType: string
{
    case NUMEROTE = 'Numéroté (Plan de salle)';
    case LIBRE = 'Placement Libre (Jauge simple)';
    case SANS_PLACE = 'Présence uniquement (AG / Émargement)';

    public function getLabel(): string
    {
        return $this->value;
    }
}
