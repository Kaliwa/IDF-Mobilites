<?php

namespace App\Service\Eligibility\Simulated;

use App\Enum\StatutVerification;
use App\Service\Eligibility\EligibilityResult;
use App\Service\Eligibility\StateEligibilityGateway;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Implémentation SIMULÉE de la récupération d'éligibilité via les services de l'État.
 *
 * Démontre l'approche hybride : certaines aides sont « disponibles à la source »
 * (CAF, CROUS) et validées sans document ; les autres exigent un justificatif
 * (donnée non exposée par l'API Particulier, ex. MDPH pour l'invalidité).
 */
#[AsAlias(id: StateEligibilityGateway::class)]
final class SimulatedStateGateway implements StateEligibilityGateway
{
    /**
     * Aides récupérables via API Particulier (avec leur source et un exemple de donnée certifiée).
     *
     * @var array<string, array{source: string, donnees: array<string, mixed>}>
     */
    private const SOURCES_DISPONIBLES = [
        'solidarite_transport' => [
            'source' => 'API Particulier · CAF',
            'donnees' => ['quotient_familial' => 480, 'reduction' => '75 %'],
        ],
        'aide_bourse' => [
            'source' => 'API Particulier · CROUS',
            'donnees' => ['echelon_bourse' => 5],
        ],
        'tarif_jeune' => [
            'source' => 'FranceConnect · Identité',
            'donnees' => ['age' => 22],
        ],
    ];

    public function recupererEligibilite(string $aideCode, array $identite = []): EligibilityResult
    {
        $disponible = self::SOURCES_DISPONIBLES[$aideCode] ?? null;

        if (null === $disponible) {
            // Donnée non exposée par l'API : on bascule vers le dépôt de justificatif.
            return new EligibilityResult(
                statut: StatutVerification::EN_ATTENTE,
                message: 'Cette aide ne peut pas être vérifiée automatiquement. Déposez un justificatif pour finaliser.',
                fallbackRequis: true,
            );
        }

        return new EligibilityResult(
            statut: StatutVerification::VALIDE,
            message: 'Éligibilité confirmée à partir de vos données certifiées.',
            source: $disponible['source'],
            donnees: $disponible['donnees'],
        );
    }
}
