<?php

namespace App\Enum;

/**
 * Statut d'un abonnement (cycle de vie de la souscription).
 */
enum StatutAbonnement: string
{
    case ACTIF = 'actif';
    case EN_ATTENTE = 'en_attente';
    case SUSPENDU = 'suspendu';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::EN_ATTENTE => 'En attente',
            self::SUSPENDU => 'Suspendu',
        };
    }
}
