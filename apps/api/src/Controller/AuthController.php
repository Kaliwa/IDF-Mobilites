<?php

namespace App\Controller;

use App\Dto\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
    ) {
    }

    #[Route('/api/register', name: 'app_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException $exception) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $payload = new RegisterRequest();
        $payload->email = isset($data['email']) ? (string) $data['email'] : null;
        $payload->password = isset($data['password']) ? (string) $data['password'] : null;

        $violations = $this->validator->validate($payload);
        if (count($violations) > 0) {
            return $this->jsonValidationErrors($violations);
        }

        $email = strtolower(trim((string) $payload->email));
        if ($this->userRepository->findOneBy(['email' => $email]) instanceof User) {
            return $this->jsonError('An account already exists for this email.', 409);
        }

        $roles = ['ROLE_USER'];
        if ('admin@comutitres.fr' === $email) {
            $roles[] = 'ROLE_ADMIN';
        }
        if ('support@comutitres.fr' === $email) {
            $roles[] = 'ROLE_SUPPORT';
        }

        $user = (new User())
            ->setEmail($email)
            ->setRoles($roles)
            ->setCreatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($user, (string) $payload->password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Account created successfully.',
            'token' => $this->jwtTokenManager->create($user),
            'user' => $this->serializeUser($user),
        ], 201);
    }

    #[Route('/api/me', name: 'app_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        return new JsonResponse([
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/api/logout', name: 'app_auth_logout', methods: ['POST'])]
    public function logout(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        return new JsonResponse(null, 204);
    }

    /**
     * @return array{id:int|null,email:string|null,roles:array<int,string>,createdAt:string|null}
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
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
