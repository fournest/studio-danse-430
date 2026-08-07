<?php

namespace App\Enum;

/**
 * Statut d'une ligne de paiement (entité Paiement).
 * Ne pas confondre avec App\Entity\StatutPaiement (statut global de l'inscription).
 */
enum StatutPaiement: string
{
    case EN_ATTENTE_REGLEMENT = 'en_attente';
    /** @deprecated Alias historique — migré vers PAIEMENT_DECLARE */
    case RECU = 'recu';
    case PAIEMENT_DECLARE = 'paiement_declare';
    case PAYE = 'encaisse';
    case ANNULE = 'annule';
    case REFUSE = 'refuse';
    /** Affichage calculé : échéance dépassée sans déclaration ni encaissement */
    case RETARD = 'retard';

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE_REGLEMENT => 'Paiement en attente',
            self::RECU => 'Déclaré par la famille',
            self::PAIEMENT_DECLARE => 'Déclaré par la famille',
            self::PAYE => 'Payé',
            self::ANNULE => 'Annulé',
            self::REFUSE => 'Refusé',
            self::RETARD => 'En retard',
        };
    }

    /**
     * Couleur de badge EasyAdmin (danger, warning, success, info…).
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::EN_ATTENTE_REGLEMENT => 'warning',
            self::RECU, self::PAIEMENT_DECLARE => 'info',
            self::PAYE => 'success',
            self::ANNULE, self::REFUSE => 'danger',
            self::RETARD => 'danger',
        };
    }

    /**
     * @return list<self>
     */
    public static function storableCases(): array
    {
        return [
            self::EN_ATTENTE_REGLEMENT,
            self::RECU,
            self::PAIEMENT_DECLARE,
            self::PAYE,
            self::ANNULE,
            self::REFUSE,
        ];
    }
}
