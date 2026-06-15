<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthcheckController
{
    #[Route('/api/health', name: 'app_healthcheck', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'application' => 'idf-mobilites-api',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'docs' => '/api',
        ]);
    }
}
