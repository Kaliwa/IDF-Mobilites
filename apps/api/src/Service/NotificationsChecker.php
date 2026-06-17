<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\LineSubscription;
use App\Entity\Payment;
use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\ORM\EntityManagerInterface;

class NotificationsChecker
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserNotificationManager $notificationManager,
        private readonly PrimClient $primClient,
    ) {
    }

    /**
     * Run all checks for a single user: abonnement expiry, failed payments and line disruptions.
     * Returns newly created notifications (already flushed).
     *
     * @return list<UserNotification>
     */
    public function checkUser(User $user): array
    {
        $created = [];

        foreach ($this->findExpiringAbonnements($user) as $abonnement) {
            $notification = $this->notificationManager->notifyAbonnementExpiry($abonnement);
            $abonnement->setRenewalNotifiedAt(new \DateTimeImmutable());
            if (null !== $notification) {
                $created[] = $notification;
            }
        }

        $failedPayments = $this->entityManager->getRepository(Payment::class)->findBy([
            'user' => $user,
            'status' => 'failed',
            'failureNotifiedAt' => null,
        ]);

        foreach ($failedPayments as $payment) {
            $notification = $this->notificationManager->notifyPaymentFailure($payment);
            $payment->setFailureNotifiedAt(new \DateTimeImmutable());
            if (null !== $notification) {
                $created[] = $notification;
            }
        }

        foreach ($this->checkLineDisruptions($user) as $notification) {
            $created[] = $notification;
        }

        if ([] !== $created) {
            $this->entityManager->flush();
        }

        return $created;
    }

    /**
     * Returns all non-support, non-admin users eligible for background notification checks.
     *
     * @return list<User>
     */
    public function getAllEligibleUsers(): array
    {
        $all = $this->entityManager->getRepository(User::class)->findAll();

        return array_values(array_filter(
            $all,
            static fn (User $user): bool => !in_array('ROLE_SUPPORT', $user->getRoles(), true)
                && !in_array('ROLE_ADMIN', $user->getRoles(), true),
        ));
    }

    /**
     * @return list<UserNotification>
     */
    private function checkLineDisruptions(User $user): array
    {
        $subscriptions = $this->entityManager->getRepository(LineSubscription::class)->findBy([
            'user' => $user,
            'enabled' => true,
        ]);

        $created = [];

        foreach ($subscriptions as $subscription) {
            $primRef = $subscription->getLine()?->getPrimRef();
            if (null === $primRef) {
                continue;
            }

            $disruptions = $this->primClient->getDisruptions($primRef);

            if ([] === $disruptions) {
                continue;
            }

            $currentIds = array_column($disruptions, 'id');
            $notifiedIds = $subscription->getNotifiedDisruptionIds();

            $newIds = array_diff($currentIds, $notifiedIds);
            // Keep only still-active IDs to self-prune the list
            $active = array_values(array_intersect($notifiedIds, $currentIds));

            foreach ($disruptions as $disruption) {
                if (!in_array($disruption['id'], $newIds, true)) {
                    continue;
                }

                $notification = $this->notificationManager->notifyLineDisruption($subscription, $disruption);
                if (null !== $notification) {
                    $created[] = $notification;
                    $active[] = $disruption['id'];
                }
            }

            $subscription->setNotifiedDisruptionIds($active);
        }

        return $created;
    }

    /**
     * @return list<Abonnement>
     */
    private function findExpiringAbonnements(User $user): array
    {
        return $this->entityManager->getRepository(Abonnement::class)
            ->createQueryBuilder('a')
            ->where('a.beneficiaire = :user')
            ->andWhere('a.dateFin > :now')
            ->andWhere('a.dateFin <= :threshold')
            ->andWhere('a.renewalNotifiedAt IS NULL OR a.renewalNotifiedAt < :weekAgo')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('threshold', new \DateTimeImmutable('+7 days'))
            ->setParameter('weekAgo', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getResult();
    }
}
