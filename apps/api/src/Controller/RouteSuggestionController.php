<?php

namespace App\Controller;

use App\Service\IdfmRouteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RouteSuggestionController
{
    public function __construct(private readonly IdfmRouteService $routeService)
    {
    }

    #[Route('/api/routes', name: 'route_suggestions', methods: ['GET'])]
    public function suggestions(Request $request): JsonResponse
    {
        $originLat = $request->query->get('originLat');
        $originLng = $request->query->get('originLng');
        $destinationLat = $request->query->get('destinationLat');
        $destinationLng = $request->query->get('destinationLng');
        $originName = $request->query->get('originName', 'Origine');
        $destinationName = $request->query->get('destinationName', 'Destination');

        if (!is_numeric($originLat) || !is_numeric($originLng) || !is_numeric($destinationLat) || !is_numeric($destinationLng)) {
            return new JsonResponse(['message' => 'Paramètres de coordonnées invalides.'], 400);
        }

        $origin = [
            'lat' => (float) $originLat,
            'lng' => (float) $originLng,
            'name' => is_string($originName) ? $originName : 'Origine',
        ];
        $destination = [
            'lat' => (float) $destinationLat,
            'lng' => (float) $destinationLng,
            'name' => is_string($destinationName) ? $destinationName : 'Destination',
        ];

        $items = $this->routeService->suggest($origin, $destination);

        return new JsonResponse(['routes' => $items]);
    }
}

