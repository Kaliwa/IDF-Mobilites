<?php

namespace App\Enum;

/**
 * Moyen de paiement utilisé par un payeur.
 */
enum MoyenPaiement: string
{
    case CARTE_BANCAIRE = 'carte_bancaire';
    case PRELEVEMENT = 'prelevement';
    case CHEQUE = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::CARTE_BANCAIRE => 'Carte bancaire',
            self::PRELEVEMENT => 'Prélèvement',
            self::CHEQUE => 'Chèque',
        };
    }
}
