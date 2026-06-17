<?php

namespace App\Service\Eligibility;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Couture vers le contrôle d'un justificatif déposé par l'usager (vérification 2D-Doc / OCR).
 *
 * L'implémentation actuelle est simulée : remplacer par une vérification de la
 * signature 2D-Doc ou un service d'analyse documentaire, sans toucher au reste du code.
 */
interface DocumentChecker
{
    public function controler(string $aideCode, UploadedFile $fichier): EligibilityResult;
}
