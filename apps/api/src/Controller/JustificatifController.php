<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JourneyRepository;
use App\Service\DisruptionService;
use App\Service\JustificatifPdfBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class JustificatifController
{
    public function __construct(
        private readonly JourneyRepository $journeyRepository,
        private readonly DisruptionService $disruptionService,
        private readonly JustificatifPdfBuilder $pdfBuilder,
    ) {
    }

    #[Route('/api/journeys/{id}/justificatif', name: 'journey_justificatif', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function generate(int $id, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Authentication required.'], 401);
        }

        $journey = $this->journeyRepository->find($id);
        if (!$journey || $journey->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Journey not found.'], 404);
        }

        $currentDisruptions = $this->disruptionService->fetchCurrentForJourney($journey);

        if ($currentDisruptions === []) {
            return new JsonResponse([
                'message' => 'Aucune perturbation en cours sur vos lignes. Le justificatif n\'est disponible qu\'en cas d\'incident actif au moment de la demande (panne, interruption, incident…).',
                'code' => 'NO_ACTIVE_DISRUPTION',
            ], 422);
        }

        $disruption = $currentDisruptions[0];
        $pdf = $this->pdfBuilder->build($user, $journey, $disruption);

        $filename = sprintf(
            'justificatif-%s.pdf',
            (new \DateTimeImmutable())->format('Ymd_His'),
        );

        $response = new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

        return $response;
    }
}

