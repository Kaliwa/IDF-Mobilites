<?php

namespace App\Controller;

use App\Entity\LineSubscription;
use App\Entity\SupportConversation;
use App\Entity\SupportMessage;
use App\Entity\TransitLine;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Service\MessagingBootstrapper;
use App\Service\MessagingRealtimePublisher;
use App\Service\UserNotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/messaging')]
final class MessagingController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessagingBootstrapper $bootstrapper,
        private readonly MessagingRealtimePublisher $realtimePublisher,
        private readonly UserNotificationManager $notificationManager,
        private readonly Security $security,
    ) {
    }

    #[Route('/overview', name: 'app_messaging_overview', methods: ['GET'])]
    public function overview(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $this->bootstrapper->ensureDemoDataFor($user);

        $notifications = $this->entityManager->getRepository(UserNotification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
        );
        $subscriptions = $this->entityManager->getRepository(LineSubscription::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'ASC'],
        );
        $conversations = $this->entityManager->getRepository(SupportConversation::class)->findBy(
            ['user' => $user],
            ['updatedAt' => 'DESC'],
        );

        return new JsonResponse([
            'notifications' => array_map($this->serializeNotification(...), $notifications),
            'subscriptions' => array_map($this->serializeSubscription(...), $subscriptions),
            'conversations' => array_map($this->serializeConversation(...), $conversations),
        ]);
    }

    #[Route('/lines', name: 'app_messaging_lines', methods: ['GET'])]
    public function lines(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $allLines = $this->entityManager->getRepository(TransitLine::class)->findBy([], ['name' => 'ASC']);

        $subscribedLineIds = array_map(
            static fn (LineSubscription $s): ?int => $s->getLine()?->getId(),
            $this->entityManager->getRepository(LineSubscription::class)->findBy(['user' => $user]),
        );

        $lines = array_values(array_filter(
            $allLines,
            static fn (TransitLine $line): bool => !in_array($line->getId(), $subscribedLineIds, true),
        ));

        return new JsonResponse([
            'lines' => array_map(
                static fn (TransitLine $line): array => [
                    'id' => (int) $line->getId(),
                    'code' => (string) $line->getCode(),
                    'name' => (string) $line->getName(),
                ],
                $lines,
            ),
        ]);
    }

    #[Route('/subscriptions', name: 'app_messaging_subscription_create', methods: ['POST'])]
    public function createSubscription(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $lineId = (int) ($payload['lineId'] ?? 0);
        if (0 === $lineId) {
            return $this->jsonError('lineId is required.', 422);
        }

        $line = $this->entityManager->getRepository(TransitLine::class)->find($lineId);
        if (!$line instanceof TransitLine) {
            return $this->jsonError('Line not found.', 404);
        }

        $existing = $this->entityManager->getRepository(LineSubscription::class)->findOneBy([
            'user' => $user,
            'line' => $line,
        ]);
        if (null !== $existing) {
            return new JsonResponse(['subscription' => $this->serializeSubscription($existing)], 200);
        }

        $channels = isset($payload['channels']) && is_array($payload['channels'])
            ? array_values(array_filter(
                array_map('strval', $payload['channels']),
                static fn (string $c): bool => in_array($c, ['inApp', 'email', 'push'], true),
            ))
            : ['inApp'];

        $subscription = (new LineSubscription())
            ->setUser($user)
            ->setLine($line)
            ->setEnabled(true)
            ->setChannels($channels)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return new JsonResponse(['subscription' => $this->serializeSubscription($subscription)], 201);
    }

    #[Route('/notifications/read-all', name: 'app_messaging_read_all', methods: ['POST'])]
    public function readAll(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $notifications = $this->entityManager->getRepository(UserNotification::class)->findBy(['user' => $user]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/notifications/{id}/read', name: 'app_messaging_read_one', methods: ['POST'])]
    public function readOne(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $notification = $this->entityManager->getRepository(UserNotification::class)->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (!$notification instanceof UserNotification) {
            return $this->jsonError('Notification not found.', 404);
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return new JsonResponse(['notification' => $this->serializeNotification($notification)]);
    }

    #[Route('/subscriptions/{id}', name: 'app_messaging_subscription_update', methods: ['POST'])]
    public function updateSubscription(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $subscription = $this->entityManager->getRepository(LineSubscription::class)->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (!$subscription instanceof LineSubscription) {
            return $this->jsonError('Subscription not found.', 404);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        if (isset($payload['enabled'])) {
            $subscription->setEnabled((bool) $payload['enabled']);
        }

        if (isset($payload['channels']) && is_array($payload['channels'])) {
            $channels = array_values(array_filter(
                array_map('strval', $payload['channels']),
                static fn (string $channel): bool => in_array($channel, ['inApp', 'email', 'push'], true),
            ));
            $subscription->setChannels($channels);
        }

        $this->entityManager->flush();

        return new JsonResponse(['subscription' => $this->serializeSubscription($subscription)]);
    }

    #[Route('/subscriptions/{id}', name: 'app_messaging_subscription_delete', methods: ['DELETE'])]
    public function deleteSubscription(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $subscription = $this->entityManager->getRepository(LineSubscription::class)->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (!$subscription instanceof LineSubscription) {
            return $this->jsonError('Subscription not found.', 404);
        }

        $this->entityManager->remove($subscription);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }

    #[Route('/conversations/{id}/messages', name: 'app_messaging_reply', methods: ['POST'])]
    public function reply(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        $conversation = $this->entityManager->getRepository(SupportConversation::class)->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (!$conversation instanceof SupportConversation) {
            return $this->jsonError('Conversation not found.', 404);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $content = trim((string) ($payload['content'] ?? ''));
        if ('' === $content) {
            return $this->jsonError('Message content is required.', 422);
        }

        $message = (new SupportMessage())
            ->setAuthor('user')
            ->setContent($content)
            ->setSentAt(new \DateTimeImmutable());

        $conversation
            ->addMessage($message)
            ->setStatus('open')
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $serializedConversation = $this->serializeConversation($conversation);
        $serializedSupportConversation = $this->serializeSupportConversation($conversation);
        $this->realtimePublisher->publishConversationUpdate(
            $conversation,
            $serializedConversation,
            $serializedSupportConversation,
        );

        return new JsonResponse(['conversation' => $serializedConversation], 201);
    }

    #[Route('/support/conversations', name: 'app_support_conversations', methods: ['GET'])]
    public function supportConversations(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        if (!$this->canAccessSupport($user)) {
            return $this->jsonError('Support access required.', 403);
        }

        $conversations = $this->entityManager->getRepository(SupportConversation::class)->findBy(
            [],
            ['updatedAt' => 'DESC'],
        );

        return new JsonResponse([
            'conversations' => array_map($this->serializeSupportConversation(...), $conversations),
        ]);
    }

    #[Route('/support/conversations/{id}/messages', name: 'app_support_reply', methods: ['POST'])]
    public function supportReply(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        if (!$this->canAccessSupport($user)) {
            return $this->jsonError('Support access required.', 403);
        }

        $conversation = $this->entityManager->getRepository(SupportConversation::class)->find($id);

        if (!$conversation instanceof SupportConversation) {
            return $this->jsonError('Conversation not found.', 404);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $content = trim((string) ($payload['content'] ?? ''));
        if ('' === $content) {
            return $this->jsonError('Message content is required.', 422);
        }

        $message = (new SupportMessage())
            ->setAuthor('service')
            ->setContent($content)
            ->setSentAt(new \DateTimeImmutable());

        $requestedStatus = (string) ($payload['status'] ?? '');
        $nextStatus = in_array($requestedStatus, ['resolved', 'open'], true)
            ? $requestedStatus
            : 'waiting-user';

        $conversation
            ->addMessage($message)
            ->setStatus($nextStatus)
            ->setUpdatedAt(new \DateTimeImmutable());

        $createdNotification = $this->notificationManager->notifySupportReply($conversation);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $serializedConversation = $this->serializeConversation($conversation);
        $serializedSupportConversation = $this->serializeSupportConversation($conversation);
        $this->realtimePublisher->publishConversationUpdate(
            $conversation,
            $serializedConversation,
            $serializedSupportConversation,
        );
        if (null !== $createdNotification) {
            $this->realtimePublisher->publishNotificationUpdate(
                $createdNotification,
                $this->serializeNotification($createdNotification),
            );
        }

        return new JsonResponse(['conversation' => $serializedSupportConversation], 201);
    }

    #[Route('/support/conversations/{id}/status', name: 'app_support_status', methods: ['POST'])]
    public function supportStatus(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->jsonError('Authentication required.', 401);
        }

        if (!$this->canAccessSupport($user)) {
            return $this->jsonError('Support access required.', 403);
        }

        $conversation = $this->entityManager->getRepository(SupportConversation::class)->find($id);

        if (!$conversation instanceof SupportConversation) {
            return $this->jsonError('Conversation not found.', 404);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->jsonError('Invalid JSON payload.', 400);
        }

        $status = (string) ($payload['status'] ?? '');
        if (!in_array($status, ['open', 'waiting-user', 'resolved'], true)) {
            return $this->jsonError('Invalid status.', 422);
        }

        $conversation
            ->setStatus($status)
            ->setUpdatedAt(new \DateTimeImmutable());

        $createdNotification = match ($status) {
            'resolved' => $this->notificationManager->notifyConversationResolved($conversation),
            'open' => $this->notificationManager->notifyConversationReopened($conversation),
            default => null,
        };

        $this->entityManager->flush();

        $serializedConversation = $this->serializeConversation($conversation);
        $serializedSupportConversation = $this->serializeSupportConversation($conversation);
        $this->realtimePublisher->publishConversationUpdate(
            $conversation,
            $serializedConversation,
            $serializedSupportConversation,
        );
        if (null !== $createdNotification) {
            $this->realtimePublisher->publishNotificationUpdate(
                $createdNotification,
                $this->serializeNotification($createdNotification),
            );
        }

        return new JsonResponse(['conversation' => $serializedSupportConversation]);
    }

    /**
     * @return array{id:int,title:string,body:string,category:string,priority:string,isRead:bool,createdAt:string,line:array{id:int,code:string,name:string}|null,actionLabel:string|null}
     */
    private function serializeNotification(UserNotification $notification): array
    {
        return [
            'id' => (int) $notification->getId(),
            'title' => (string) $notification->getTitle(),
            'body' => (string) $notification->getBody(),
            'category' => (string) $notification->getCategory(),
            'priority' => (string) $notification->getPriority(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'actionLabel' => $notification->getActionLabel(),
            'line' => $notification->getLine() ? [
                'id' => (int) $notification->getLine()->getId(),
                'code' => (string) $notification->getLine()->getCode(),
                'name' => (string) $notification->getLine()->getName(),
            ] : null,
        ];
    }

    /**
     * @return array{id:int,enabled:bool,channels:array<int,string>,line:array{id:int,code:string,name:string}}
     */
    private function serializeSubscription(LineSubscription $subscription): array
    {
        $line = $subscription->getLine();

        return [
            'id' => (int) $subscription->getId(),
            'enabled' => $subscription->isEnabled(),
            'channels' => $subscription->getChannels(),
            'line' => [
                'id' => (int) $line?->getId(),
                'code' => (string) $line?->getCode(),
                'name' => (string) $line?->getName(),
            ],
        ];
    }

    /**
     * @return array{id:int,subject:string,status:string,updatedAt:string|null,messages:array<int,array{id:int,author:string,content:string,sentAt:string|null}>}
     */
    private function serializeConversation(SupportConversation $conversation): array
    {
        return [
            'id' => (int) $conversation->getId(),
            'subject' => (string) $conversation->getSubject(),
            'status' => (string) $conversation->getStatus(),
            'updatedAt' => $conversation->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'messages' => array_map(
                static fn (SupportMessage $message): array => [
                    'id' => (int) $message->getId(),
                    'author' => (string) $message->getAuthor(),
                    'content' => (string) $message->getContent(),
                    'sentAt' => $message->getSentAt()?->format(\DateTimeInterface::ATOM),
                ],
                $conversation->getMessages()->toArray(),
            ),
        ];
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }

    /**
     * @return array{id:int,subject:string,status:string,updatedAt:string|null,customer:array{id:int|null,email:string|null},messages:array<int,array{id:int,author:string,content:string,sentAt:string|null}>}
     */
    private function serializeSupportConversation(SupportConversation $conversation): array
    {
        return [
            'id' => (int) $conversation->getId(),
            'subject' => (string) $conversation->getSubject(),
            'status' => (string) $conversation->getStatus(),
            'updatedAt' => $conversation->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'customer' => [
                'id' => $conversation->getUser()?->getId(),
                'email' => $conversation->getUser()?->getEmail(),
            ],
            'messages' => array_map(
                static fn (SupportMessage $message): array => [
                    'id' => (int) $message->getId(),
                    'author' => (string) $message->getAuthor(),
                    'content' => (string) $message->getContent(),
                    'sentAt' => $message->getSentAt()?->format(\DateTimeInterface::ATOM),
                ],
                $conversation->getMessages()->toArray(),
            ),
        ];
    }

    private function canAccessSupport(User $user): bool
    {
        return in_array('ROLE_SUPPORT', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true)
            || $this->security->isGranted('ROLE_SUPPORT')
            || $this->security->isGranted('ROLE_ADMIN');
    }
}
