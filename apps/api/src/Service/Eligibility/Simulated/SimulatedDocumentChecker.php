<?php

namespace App\Service\Eligibility\Simulated;

use App\Enum\StatutVerification;
use App\Service\Eligibility\DocumentChecker;
use App\Service\Eligibility\EligibilityResult;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Implémentation SIMULÉE du contrôle d'un justificatif (vérification 2D-Doc / OCR).
 *
 * Règle de simulation, déterministe et démontrable :
 *  - un document dont le nom contient « faux » ou « invalide » est refusé ;
 *  - tout autre document d'un type accepté est considéré comme valide.
 */
#[AsAlias(id: DocumentChecker::class)]
final class SimulatedDocumentChecker implements DocumentChecker
{
    public function controler(string $aideCode, UploadedFile $fichier): EligibilityResult
    {
        $nom = strtolower($fichier->getClientOriginalName());

        if (str_contains($nom, 'faux') || str_contains($nom, 'invalide')) {
            return new EligibilityResult(
                statut: StatutVerification::REFUSE,
                message: 'Le justificatif n\'a pas pu être authentifié. Vérifiez le document et réessayez.',
                source: 'Justificatif · 2D-Doc',
            );
        }

        return new EligibilityResult(
            statut: StatutVerification::VALIDE,
            message: 'Justificatif authentifié avec succès.',
            source: 'Justificatif · 2D-Doc',
            donnees: ['fichier' => $fichier->getClientOriginalName()],
        );
    }
}
