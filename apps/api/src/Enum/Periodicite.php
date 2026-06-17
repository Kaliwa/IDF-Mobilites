<?php

namespace App\Enum;

/**
 * Périodicité de facturation d'un abonnement.
 */
enum Periodicite: string
{
    case MENSUEL = 'mensuel';
    case ANNUEL = 'annuel';

    public function label(): string
    {
        return match ($this) {
            self::MENSUEL => 'Mensuel',
            self::ANNUEL => 'Annuel',
        };
    }
}
