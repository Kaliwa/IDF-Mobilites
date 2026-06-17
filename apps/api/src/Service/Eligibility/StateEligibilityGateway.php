<?php

namespace App\Service\Eligibility;

/**
 * Couture vers les services de l'État (FranceConnect + API Particulier).
 *
 * Récupère, avec le consentement de l'usager, une donnée certifiée à la source
 * (quotient familial CAF, statut boursier CROUS, etc.) sans demander de document.
 *
 * L'implémentation actuelle est simulée : remplacer par un client HTTP réel
 * une fois l'habilitation API Particulier obtenue, sans toucher au reste du code.
 */
interface StateEligibilityGateway
{
    /**
     * @param array<string, mixed> $identite Identité issue de FranceConnect (facultative en simulation).
     */
    public function recupererEligibilite(string $aideCode, array $identite = []): EligibilityResult;
}
