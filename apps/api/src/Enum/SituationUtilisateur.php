<?php

namespace App\Enum;

/**
 * Situation de vie d'un utilisateur, point d'entrée de l'orientation par événements de vie.
 */
enum SituationUtilisateur: string
{
    case ETUDIANT = 'etudiant';
    case SALARIE = 'salarie';
    case RETRAITE = 'retraite';
    case SCOLAIRE = 'scolaire';
    case NOUVEAU_RESIDENT = 'nouveau_resident';
    case AUTRE = 'autre';

    /**
     * Libellé lisible (affichage front).
     */
    public function label(): string
    {
        return match ($this) {
            self::ETUDIANT => 'Étudiant',
            self::SALARIE => 'Salarié',
            self::RETRAITE => 'Retraité',
            self::SCOLAIRE => 'Scolaire',
            self::NOUVEAU_RESIDENT => 'Nouveau résident',
            self::AUTRE => 'Autre',
        };
    }
}
