<?php

namespace App\Enum;

/**
 * Méthode utilisée pour vérifier l'éligibilité à une aide.
 */
enum MethodeVerification: string
{
    /** Donnée certifiée récupérée à la source (FranceConnect + API Particulier). */
    case FRANCE_CONNECT = 'france_connect';

    /** Justificatif déposé par l'usager puis contrôlé (2D-Doc / OCR). */
    case JUSTIFICATIF = 'justificatif';

    public function label(): string
    {
        return match ($this) {
            self::FRANCE_CONNECT => 'FranceConnect',
            self::JUSTIFICATIF => 'Justificatif',
        };
    }
}
