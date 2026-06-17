<?php

namespace App\Controller;

use App\Entity\Journey;
use App\Entity\User;
use App\Repository\JourneyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class JourneyController
{
    public function __construct(
        private readonly JourneyRepository $journeyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/journeys', name: 'journey_index', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->unauthorized();
        }

        $journeys = $this->journeyRepository->findForUser($user);
        return new JsonResponse([
            'items' => array_map($this->serializeJourney(...), $journeys),
        ]);
    }

    #[Route('/api/journeys', name: 'journey_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->unauthorized();
        }

        $payload = $this->parsePayload($request);
        if (!$payload['ok']) {
            return $this->validationError($payload['message']);
        }

        $journey = (new Journey())
            ->setUser($user)
            ->setLabel($payload['data']['label'])
            ->setOriginName($payload['data']['originName'])
            ->setOriginLat($payload['data']['originLat'])
            ->setOriginLng($payload['data']['originLng'])
            ->setDestinationName($payload['data']['destinationName'])
            ->setDestinationLat($payload['data']['destinationLat'])
            ->setDestinationLng($payload['data']['destinationLng'])
            ->setLines($payload['data']['lines']);

        $this->entityManager->persist($journey);
        $this->entityManager->flush();

        return new JsonResponse([
            'journey' => $this->serializeJourney($journey),
        ], 201);
    }

    #[Route('/api/journeys/{id}', name: 'journey_update', requirements: ['id' => '\d+'], methods: ['PATCH', 'PUT'])]
    public function update(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->unauthorized();
        }

        $journey = $this->journeyRepository->find($id);
        if (!$journey instanceof Journey || $journey->getUser()?->getId() !== $user->getId()) {
            return $this->notFound();
        }

        $payload = $this->parsePayload($request, allowPartial: true);
        if (!$payload['ok']) {
            return $this->validationError($payload['message']);
        }

        $data = $payload['data'];
        if (isset($data['label'])) {
            $journey->setLabel($data['label']);
        }
        if (isset($data['originName'])) {
            $journey->setOriginName($data['originName']);
        }
        if (isset($data['originLat'])) {
            $journey->setOriginLat($data['originLat']);
        }
        if (isset($data['originLng'])) {
            $journey->setOriginLng($data['originLng']);
        }
        if (isset($data['destinationName'])) {
            $journey->setDestinationName($data['destinationName']);
        }
        if (isset($data['destinationLat'])) {
            $journey->setDestinationLat($data['destinationLat']);
        }
        if (isset($data['destinationLng'])) {
            $journey->setDestinationLng($data['destinationLng']);
        }
        if (array_key_exists('lines', $data)) {
            $journey->setLines($data['lines']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'journey' => $this->serializeJourney($journey),
        ]);
    }

    #[Route('/api/journeys/{id}', name: 'journey_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->unauthorized();
        }

        $journey = $this->journeyRepository->find($id);
        if (!$journey instanceof Journey || $journey->getUser()?->getId() !== $user->getId()) {
            return $this->notFound();
        }

        $this->entityManager->remove($journey);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }

    /**
     * @return array{ok:bool,message?:string,data?:array<string,mixed>}
     */
    private function parsePayload(Request $request, bool $allowPartial = false): array
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException $exception) {
            return ['ok' => false, 'message' => 'Invalid JSON payload.'];
        }

        $fields = [
            'label' => fn ($value) => is_string($value) && trim($value) !== '',
            'originName' => fn ($value) => is_string($value) && trim($value) !== '',
            'originLat' => fn ($value) => is_numeric($value),
            'originLng' => fn ($value) => is_numeric($value),
            'destinationName' => fn ($value) => is_string($value) && trim($value) !== '',
            'destinationLat' => fn ($value) => is_numeric($value),
            'destinationLng' => fn ($value) => is_numeric($value),
            'lines' => fn ($value) => is_array($value) || $value === null,
        ];

        $normalized = [];
        foreach ($fields as $field => $validator) {
            $has = array_key_exists($field, $data);
            if (!$has && $allowPartial) {
                continue;
            }
            if (!$has) {
                return ['ok' => false, 'message' => sprintf('Field "%s" is required.', $field)];
            }
            $value = $data[$field];
            if (!$validator($value)) {
                return ['ok' => false, 'message' => sprintf('Field "%s" is invalid.', $field)];
            }
            $normalized[$field] = is_string($value) ? trim($value) : (is_numeric($value) ? (float) $value : $value);
        }

        return ['ok' => true, 'data' => $normalized];
    }

    private function serializeJourney(Journey $journey): array
    {
        return [
            'id' => $journey->getId(),
            'label' => $journey->getLabel(),
            'origin' => [
                'name' => $journey->getOriginName(),
                'lat' => $journey->getOriginLat(),
                'lng' => $journey->getOriginLng(),
            ],
            'destination' => [
                'name' => $journey->getDestinationName(),
                'lat' => $journey->getDestinationLat(),
                'lng' => $journey->getDestinationLng(),
            ],
            'lines' => $journey->getLines(),
            'createdAt' => $journey->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $journey->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function unauthorized(): JsonResponse
    {
        return new JsonResponse(['message' => 'Authentication required.'], 401);
    }

    private function validationError(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], 422);
    }

    private function notFound(): JsonResponse
    {
        return new JsonResponse(['message' => 'Journey not found.'], 404);
    }
}

