<?php

namespace App\Enum;

enum StatutInscription: string
{
    case BROUILLON = 'brouillon';
    case EN_ATTENTE_VALIDATION = 'en_attente_validation';
    case VALIDE = 'valide';
    case ANNULE = 'annule';

    public function getLabel(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::EN_ATTENTE_VALIDATION => 'En attente de validation',
            self::VALIDE => 'Validée',
            self::ANNULE => 'Annulée',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::BROUILLON => 'secondary',
            self::EN_ATTENTE_VALIDATION => 'warning',
            self::VALIDE => 'success',
            self::ANNULE => 'danger',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::BROUILLON;
    }
}
