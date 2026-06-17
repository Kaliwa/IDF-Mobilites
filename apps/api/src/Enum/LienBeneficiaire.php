<?php

namespace App\Enum;

/**
 * Lien entre le payeur et le bénéficiaire de l'abonnement
 * (ex. un parent qui paie pour son enfant).
 */
enum LienBeneficiaire: string
{
    case PARENT = 'parent';
    case CONJOINT = 'conjoint';
    case ENFANT = 'enfant';
    case TUTEUR = 'tuteur';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::PARENT => 'Parent',
            self::CONJOINT => 'Conjoint',
            self::ENFANT => 'Enfant',
            self::TUTEUR => 'Tuteur',
            self::AUTRE => 'Autre',
        };
    }
}
