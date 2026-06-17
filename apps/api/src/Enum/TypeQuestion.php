<?php

namespace App\Enum;

/**
 * Type d'interaction d'une question du parcours d'orientation.
 */
enum TypeQuestion: string
{
    case CHOIX_UNIQUE = 'single_choice';
    case CHOIX_MULTIPLE = 'multi_choice';
}
