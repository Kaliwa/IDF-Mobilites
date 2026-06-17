<?php

namespace App\Service\Eligibility;

use App\Enum\StatutVerification;

/**
 * Résultat d'une tentative de vérification d'éligibilité, renvoyé par un vérificateur.
 */
final class EligibilityResult
{
    /**
     * @param array<string, mixed> $donnees Données certifiées renvoyées par la source (facultatif).
     */
    public function __construct(
        public readonly StatutVerification $statut,
        public readonly string $message,
        public readonly ?string $source = null,
        public readonly array $donnees = [],
        /** Indique qu'aucune donnée n'a pu être récupérée et qu'un justificatif est nécessaire. */
        public readonly bool $fallbackRequis = false,
    ) {
    }

    public function estValide(): bool
    {
        return StatutVerification::VALIDE === $this->statut;
    }
}
