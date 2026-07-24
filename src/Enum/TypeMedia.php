<?php

namespace App\Enum;

enum TypeMedia: string
{
    case IMAGE_LOCAL = 'IMAGE_LOCAL';
    case INSTAGRAM   = 'INSTAGRAM';
    case FACEBOOK    = 'FACEBOOK';
    case YOUTUBE     = 'YOUTUBE';
}