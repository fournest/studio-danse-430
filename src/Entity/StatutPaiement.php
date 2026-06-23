<?php
namespace App\Entity;

enum StatutPaiement: string
{
    case NON_PAYE = 'Non payé';
    case PARTIEL = 'Partiel';
    case SOLDE = 'Soldé';
}
