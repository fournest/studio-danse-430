<?php

namespace App\Enum;

enum StatutSante: string
{
    case EN_ATTENTE = 'en_attente';
    case QS_SPORT_VALIDE = 'qs_sport_valide';
    case CERTIFICAT_FOURNI = 'certificat_fourni';
    case VALIDE_BUREAU = 'valide_bureau';

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::QS_SPORT_VALIDE => 'QS-Sport validé',
            self::CERTIFICAT_FOURNI => 'Certificat déposé',
            self::VALIDE_BUREAU => 'Validé bureau',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'warning',
            self::QS_SPORT_VALIDE => 'info',
            self::CERTIFICAT_FOURNI => 'primary',
            self::VALIDE_BUREAU => 'success',
        };
    }
}
