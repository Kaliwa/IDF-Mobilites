<?php

namespace App\Service\Eligibility;

use App\Entity\EligibilityCheck;
use App\Enum\MethodeVerification;
use App\Exception\OrientationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Orchestre la vérification d'éligibilité (approche hybride) et en conserve la trace.
 *
 * 1. Tente d'abord la voie « État » (FranceConnect + API Particulier) ;
 * 2. à défaut, l'usager dépose un justificatif contrôlé (2D-Doc / OCR).
 */
final class EligibilityService
{
    private const TAILLE_MAX = 5 * 1024 * 1024; // 5 Mo
    private const TYPES_AUTORISES = ['application/pdf', 'image/jpeg', 'image/png'];

    public function __construct(
        private readonly StateEligibilityGateway $stateGateway,
        private readonly DocumentChecker $documentChecker,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%/var/uploads/justificatifs')]
        private readonly string $repertoireJustificatifs,
    ) {
    }

    /**
     * Voie 1 : vérification automatique via les services de l'État.
     *
     * @param array<string, mixed> $identite
     */
    public function verifierViaEtat(string $aideCode, array $identite = []): EligibilityResult
    {
        $resultat = $this->stateGateway->recupererEligibilite($aideCode, $identite);

        $this->tracer($aideCode, MethodeVerification::FRANCE_CONNECT, $resultat, null);

        return $resultat;
    }

    /**
     * Voie 2 (repli) : contrôle d'un justificatif déposé par l'usager.
     */
    public function verifierViaJustificatif(string $aideCode, UploadedFile $fichier): EligibilityResult
    {
        $this->validerFichier($fichier);

        $nomOriginal = $fichier->getClientOriginalName();
        $nomStocke = $this->stocker($fichier);

        $resultat = $this->documentChecker->controler($aideCode, $fichier);

        $this->tracer($aideCode, MethodeVerification::JUSTIFICATIF, $resultat, $nomOriginal, $nomStocke);

        return $resultat;
    }

    private function validerFichier(UploadedFile $fichier): void
    {
        if (!$fichier->isValid()) {
            throw OrientationException::invalid('Le fichier n\'a pas pu être téléversé.');
        }

        if ($fichier->getSize() > self::TAILLE_MAX) {
            throw OrientationException::invalid('Le fichier dépasse la taille maximale autorisée (5 Mo).');
        }

        if (!\in_array($fichier->getMimeType(), self::TYPES_AUTORISES, true)) {
            throw OrientationException::invalid('Format non accepté. Utilisez un PDF, un JPEG ou un PNG.');
        }
    }

    /**
     * Déplace le fichier dans le répertoire des justificatifs sous un nom unique.
     */
    private function stocker(UploadedFile $fichier): string
    {
        $extension = $fichier->guessExtension() ?: 'bin';
        $nom = sprintf('%s.%s', bin2hex(random_bytes(8)), $extension);

        try {
            $fichier->move($this->repertoireJustificatifs, $nom);
        } catch (FileException) {
            throw OrientationException::invalid('Le justificatif n\'a pas pu être enregistré.');
        }

        return $nom;
    }

    private function tracer(
        string $aideCode,
        MethodeVerification $methode,
        EligibilityResult $resultat,
        ?string $documentNom,
        ?string $documentStocke = null,
    ): void {
        $trace = (new EligibilityCheck())
            ->setAideCode($aideCode)
            ->setMethode($methode)
            ->setStatut($resultat->statut)
            ->setSource($resultat->source)
            ->setDocumentNom($documentStocke ?? $documentNom)
            ->setDonnees($resultat->donnees !== [] ? $resultat->donnees : null)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($trace);
        $this->entityManager->flush();
    }
}
