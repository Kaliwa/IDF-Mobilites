<?php

namespace App\Enum;

/**
 * Résultat d'une vérification d'éligibilité.
 */
enum StatutVerification: string
{
    case VALIDE = 'valide';
    case EN_ATTENTE = 'en_attente';
    case REFUSE = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::VALIDE => 'Vérifié',
            self::EN_ATTENTE => 'En attente',
            self::REFUSE => 'Refusé',
        };
    }
}
