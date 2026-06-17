<?php

namespace App\Service;

use App\Entity\SupportConversation;
use App\Entity\UserNotification;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MessagingRealtimePublisher
{
    public function __construct(private readonly HubInterface $hub)
    {
    }

    /**
     * @param array{id:int,subject:string,status:string,updatedAt:string|null,messages:array<int,array{id:int,author:string,content:string,sentAt:string|null}>} $conversation
     * @param array{id:int,subject:string,status:string,updatedAt:string|null,customer:array{id:int|null,email:string|null},messages:array<int,array{id:int,author:string,content:string,sentAt:string|null}>} $supportConversation
     */
    public function publishConversationUpdate(
        SupportConversation $entity,
        array $conversation,
        array $supportConversation,
    ): void {
        $topics = [
            self::supportTopic(),
            self::conversationTopic((int) $entity->getId()),
        ];

        $customerId = $entity->getUser()?->getId();
        if (null !== $customerId) {
            $topics[] = self::userTopic((int) $customerId);
        }

        $payload = json_encode([
            'type' => 'conversation.updated',
            'conversation' => $conversation,
            'supportConversation' => $supportConversation,
        ], JSON_THROW_ON_ERROR);

        try {
            $this->hub->publish(new Update($topics, $payload));
        } catch (\Throwable) {
            // The messaging flow should still work even if the realtime hub is temporarily unavailable.
        }
    }

    /**
     * @param array{id:int,title:string,body:string,category:string,priority:string,isRead:bool,createdAt:string,line:array{id:int,code:string,name:string}|null,actionLabel:string|null} $notification
     */
    public function publishNotificationUpdate(UserNotification $entity, array $notification): void
    {
        $userId = $entity->getUser()?->getId();
        if (null === $userId) {
            return;
        }

        $payload = json_encode([
            'type' => 'notification.created',
            'notification' => $notification,
        ], JSON_THROW_ON_ERROR);

        try {
            $this->hub->publish(new Update(
                [self::userNotificationsTopic((int) $userId)],
                $payload,
            ));
        } catch (\Throwable) {
            // The notification flow should still work even if realtime is unavailable.
        }
    }

    public static function supportTopic(): string
    {
        return 'https://comutitres.local/topics/support/conversations';
    }

    public static function userTopic(int $userId): string
    {
        return sprintf('https://comutitres.local/topics/users/%d/conversations', $userId);
    }

    public static function conversationTopic(int $conversationId): string
    {
        return sprintf('https://comutitres.local/topics/conversations/%d', $conversationId);
    }

    public static function userNotificationsTopic(int $userId): string
    {
        return sprintf('https://comutitres.local/topics/users/%d/notifications', $userId);
    }
}
