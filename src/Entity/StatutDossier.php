<?php
namespace App\Entity;

enum StatutDossier: string
{
    case EN_ATTENTE = 'En attente';
    case INCOMPLET = 'Incomplet';
    case VALIDE = 'Validé';
}
