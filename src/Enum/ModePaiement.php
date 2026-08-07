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
            self::ANCV => 'Chèque Vacances (ANCV)',
            self::PASS_SPORT => 'Pass\'Sport',
            self::VIREMENT => 'Virement bancaire',
            self::ESPECES => 'Espèces (remise sous enveloppe au club)',
            self::HELLOASSO => 'Paiement en ligne (HelloAsso)',
        };
    }

    /**
     * Modes à remettre physiquement au club (chèque / espèces / aides).
     */
    public function requiresDepotClub(): bool
    {
        return \in_array($this, [
            self::CHEQUE,
            self::ESPECES,
            self::ANCV,
            self::PASS_SPORT,
        ], true);
    }

    /**
     * Aides / autres règlements soustraits avant échelonnement du solde.
     *
     * @return list<self>
     */
    public static function modesDeductionFoyer(): array
    {
        return [
            self::PASS_SPORT,
            self::ANCV,
            self::ESPECES,
        ];
    }

    /**
     * Modes autorisés pour l’échelonnement du solde restant.
     *
     * @return list<self>
     */
    public static function modesEchelonnes(): array
    {
        return [self::CHEQUE, self::VIREMENT];
    }
}
