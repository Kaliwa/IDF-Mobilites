<?php

namespace App\Enum;

/**
 * Statut d'un compte utilisateur.
 */
enum StatutUtilisateur: string
{
    case ACTIF = 'actif';
    case INACTIF = 'inactif';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::INACTIF => 'Inactif',
        };
    }
}
