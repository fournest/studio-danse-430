<?php

namespace App\Enum;

/**
 * Statut d'une ligne de paiement (entité Paiement).
 * Ne pas confondre avec App\Entity\StatutPaiement (statut global de l'inscription).
 */
enum StatutPaiement: string
{
    case EN_ATTENTE = 'en_attente';
    case RECU = 'recu';
    case ENCAISSE = 'encaisse';
    case REFUSE = 'refuse';

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::RECU => 'Reçu',
            self::ENCAISSE => 'Encaissé',
            self::REFUSE => 'Refusé',
        };
    }

    /**
     * Couleur de badge EasyAdmin (danger, warning, success, info…).
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'warning',
            self::RECU => 'info',
            self::ENCAISSE => 'success',
            self::REFUSE => 'danger',
        };
    }
}
