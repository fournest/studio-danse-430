<?php

namespace App\Enum;

enum EventType: string
{
    case GALA = 'Gala de Danse';
    case LOTO = 'Méga Loto';
    case AG = 'Assemblée Générale';
    case STAGE = 'Stage / Atelier';

    public function getLabel(): string
    {
        return $this->value;
    }
}
