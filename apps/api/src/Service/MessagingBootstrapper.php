<?php

namespace App\Service;

use App\Entity\Forfait;
use App\Entity\LineSubscription;
use App\Entity\Payment;
use App\Entity\SupportConversation;
use App\Entity\SupportMessage;
use App\Entity\TransitLine;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class MessagingBootstrapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationsChecker $notificationsChecker,
    ) {
    }

    public function ensureDemoDataFor(User $user): void
    {
        $lines = $this->ensureLines();

        if (0 === (int) $this->entityManager->getRepository(LineSubscription::class)->count(['user' => $user])) {
            $this->seedSubscriptions($user, $lines);
        }

        if (0 === (int) $this->entityManager->getRepository(SupportConversation::class)->count(['user' => $user])) {
            $this->seedConversations($user);
        }

        if (0 === (int) $this->entityManager->getRepository(Forfait::class)->count(['user' => $user])) {
            $this->seedForfaitAndPayment($user);
        }

        $this->entityManager->flush();

        // Generate notifications from real business data (idempotent — won't duplicate)
        $this->notificationsChecker->checkUser($user);
    }

    /**
     * @return array<string, TransitLine>
     */
    private function ensureLines(): array
    {
        $catalog = [
            'rer-a' => ['name' => 'RER A', 'primRef' => 'STIF:Line::C01742:'],
            'metro-14' => ['name' => 'Métro 14', 'primRef' => 'STIF:Line::C01384:'],
            'tram-t3a' => ['name' => 'Tram T3a', 'primRef' => 'STIF:Line::C01391:'],
        ];

        $repository = $this->entityManager->getRepository(TransitLine::class);
        $lines = [];

        foreach ($catalog as $code => $data) {
            /** @var TransitLine|null $line */
            $line = $repository->findOneBy(['primRef' => $data['primRef']])
                ?? $repository->findOneBy(['code' => $code]);

            if (!$line instanceof TransitLine) {
                $line = (new TransitLine())
                    ->setCode($code)
                    ->setName($data['name'])
                    ->setPrimRef($data['primRef']);
                $this->entityManager->persist($line);
            } elseif (null === $line->getPrimRef()) {
                $line->setPrimRef($data['primRef']);
            }

            $lines[$code] = $line;
        }

        return $lines;
    }

    /**
     * @param array<string, TransitLine> $lines
     */
    private function seedSubscriptions(User $user, array $lines): void
    {
        $items = [
            ['line' => 'rer-a', 'enabled' => true, 'channels' => ['inApp', 'push']],
            ['line' => 'metro-14', 'enabled' => true, 'channels' => ['inApp']],
            ['line' => 'tram-t3a', 'enabled' => false, 'channels' => ['email']],
        ];

        foreach ($items as $item) {
            $subscription = (new LineSubscription())
                ->setUser($user)
                ->setLine($lines[$item['line']])
                ->setEnabled($item['enabled'])
                ->setChannels($item['channels'])
                ->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($subscription);
        }
    }

    private function seedForfaitAndPayment(User $user): void
    {
        $forfait = (new Forfait())
            ->setUser($user)
            ->setLabel('Navigo Mois')
            ->setPrice('86.40')
            ->setExpiresAt(new \DateTimeImmutable('+5 days'));

        $this->entityManager->persist($forfait);

        // A recent failed payment on this forfait — will trigger a payment notification
        $failed = (new Payment())
            ->setUser($user)
            ->setForfait($forfait)
            ->setAmount('86.40')
            ->setStatus('failed')
            ->setProcessedAt(new \DateTimeImmutable('-1 day'));

        $this->entityManager->persist($failed);

        // A past successful payment for historical context
        $paid = (new Payment())
            ->setUser($user)
            ->setForfait($forfait)
            ->setAmount('86.40')
            ->setStatus('paid')
            ->setProcessedAt(new \DateTimeImmutable('-32 days'));

        $this->entityManager->persist($paid);
    }

    private function seedConversations(User $user): void
    {
        $threads = [
            [
                'subject' => 'Carte endommagée',
                'status' => 'open',
                'updatedAt' => '-2 hours',
                'messages' => [
                    ['author' => 'user', 'content' => "Bonjour, mon passe n'est plus reconnu sur les bornes depuis hier.", 'sentAt' => '-1 day'],
                    ['author' => 'service', 'content' => 'Nous avons ouvert une demande de remplacement. Vous serez notifié à la prochaine étape.', 'sentAt' => '-2 hours'],
                ],
            ],
        ];

        foreach ($threads as $threadData) {
            $conversation = (new SupportConversation())
                ->setUser($user)
                ->setSubject($threadData['subject'])
                ->setStatus($threadData['status'])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable($threadData['updatedAt']));

            foreach ($threadData['messages'] as $messageData) {
                $message = (new SupportMessage())
                    ->setAuthor($messageData['author'])
                    ->setContent($messageData['content'])
                    ->setSentAt(new \DateTimeImmutable($messageData['sentAt']));

                $conversation->addMessage($message);
            }

            $this->entityManager->persist($conversation);
        }
    }
}
