<?php

namespace App\Controller;

use App\Dto\OrientationNextRequest;
use App\Exception\OrientationException;
use App\Service\OrientationEngine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API du parcours d'orientation par événements de vie.
 * Routes publiques (tunnel pré-inscription) : voir config/packages/security.yaml.
 */
final class OrientationController
{
    public function __construct(
        private readonly OrientationEngine $orientationEngine,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/orientation/events', name: 'app_orientation_events', methods: ['GET'])]
    public function events(): JsonResponse
    {
        return new JsonResponse([
            'events' => $this->orientationEngine->listEvents(),
        ]);
    }

    #[Route('/api/orientation/next', name: 'app_orientation_next', methods: ['POST'])]
    public function next(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $payload = new OrientationNextRequest();
        $payload->scenario = isset($data['scenario']) ? (string) $data['scenario'] : null;
        $payload->currentQuestion = isset($data['currentQuestion']) ? (string) $data['currentQuestion'] : null;
        $payload->answers = isset($data['answers']) && \is_array($data['answers'])
            ? array_values(array_map('strval', $data['answers']))
            : [];

        $violations = $this->validator->validate($payload);
        if (count($violations) > 0) {
            return $this->jsonValidationErrors($violations);
        }

        try {
            $result = $this->orientationEngine->resolveNext($payload);
        } catch (OrientationException $exception) {
            return $this->jsonError($exception->getMessage(), $exception->getStatusCode());
        }

        return new JsonResponse($result);
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
        ], $status);
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
