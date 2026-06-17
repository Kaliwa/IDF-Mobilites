<?php

namespace App\Controller;

use App\Dto\EligibilityStateRequest;
use App\Exception\OrientationException;
use App\Service\Eligibility\EligibilityResult;
use App\Service\Eligibility\EligibilityService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Vérification d'éligibilité aux aides (approche hybride État + justificatif).
 * Routes publiques (tunnel pré-inscription) : voir config/packages/security.yaml.
 */
final class EligibilityController
{
    public function __construct(
        private readonly EligibilityService $eligibilityService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Voie 1 : vérification automatique via FranceConnect + API Particulier (simulée).
     */
    #[Route('/api/orientation/eligibility/etat', name: 'app_eligibility_etat', methods: ['POST'])]
    public function etat(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $payload = new EligibilityStateRequest();
        $payload->aideCode = isset($data['aideCode']) ? (string) $data['aideCode'] : null;

        $violations = $this->validator->validate($payload);
        if (count($violations) > 0) {
            return $this->jsonValidationErrors($violations);
        }

        try {
            $result = $this->eligibilityService->verifierViaEtat((string) $payload->aideCode);
        } catch (OrientationException $exception) {
            return $this->jsonError($exception->getMessage(), $exception->getStatusCode());
        }

        return new JsonResponse($this->serialize($result));
    }

    /**
     * Voie 2 (repli) : dépôt d'un justificatif contrôlé (2D-Doc / OCR, simulé).
     */
    #[Route('/api/orientation/eligibility/justificatif', name: 'app_eligibility_justificatif', methods: ['POST'])]
    public function justificatif(Request $request): JsonResponse
    {
        $aideCode = (string) $request->request->get('aideCode', '');
        $document = $request->files->get('document');

        if ('' === $aideCode) {
            return $this->jsonError('Le code de l\'aide est requis.', 422);
        }

        if (!$document instanceof UploadedFile) {
            return $this->jsonError('Aucun justificatif fourni.', 422);
        }

        try {
            $result = $this->eligibilityService->verifierViaJustificatif($aideCode, $document);
        } catch (OrientationException $exception) {
            return $this->jsonError($exception->getMessage(), $exception->getStatusCode());
        }

        return new JsonResponse($this->serialize($result));
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(EligibilityResult $result): array
    {
        return [
            'statut' => $result->statut->value,
            'statutLabel' => $result->statut->label(),
            'message' => $result->message,
            'source' => $result->source,
            'donnees' => $result->donnees,
            'fallbackRequis' => $result->fallbackRequis,
        ];
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }

    /**
     * @param iterable<ConstraintViolationInterface> $violations
     */
    private function jsonValidationErrors(iterable $violations): JsonResponse
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return new JsonResponse([
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }
}
