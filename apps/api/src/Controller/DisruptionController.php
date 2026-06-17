<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JourneyRepository;
use App\Service\DisruptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class DisruptionController
{
    public function __construct(
        private readonly JourneyRepository $journeyRepository,
        private readonly DisruptionService $disruptionService,
    ) {
    }

    #[Route('/api/disruptions', name: 'journey_disruptions', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Authentication required.'], 401);
        }

        $journeyId = $request->query->getInt('journeyId', 0);
        if ($journeyId <= 0) {
            return new JsonResponse(['message' => 'journeyId is required.'], 400);
        }

        $journey = $this->journeyRepository->find($journeyId);
        if (!$journey || $journey->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Journey not found.'], 404);
        }

        $result = $this->disruptionService->fetchForJourney($journey);

        return new JsonResponse([
            'journeyId' => $journey->getId(),
            'checkedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'checkedLines' => $result['checkedLines'],
            'currentDisruptions' => $result['currentDisruptions'],
            'plannedDisruptions' => $result['plannedDisruptions'],
            'disruptions' => $result['currentDisruptions'],
            'summary' => [
                'linesChecked' => count($result['checkedLines']),
                'linesResolved' => count(array_filter(
                    $result['checkedLines'],
                    static fn (array $line): bool => (bool) ($line['resolved'] ?? false),
                )),
                'currentDisruptionsFound' => count($result['currentDisruptions']),
                'plannedDisruptionsFound' => count($result['plannedDisruptions']),
                'canGenerateJustificatif' => count($result['currentDisruptions']) > 0,
            ],
        ]);
    }
}


