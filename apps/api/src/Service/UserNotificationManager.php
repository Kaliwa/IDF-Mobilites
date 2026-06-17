<?php

namespace App\Service;

use App\Entity\Forfait;
use App\Entity\LineSubscription;
use App\Entity\Payment;
use App\Entity\SupportConversation;
use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationManager
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function notifySupportReply(SupportConversation $conversation): ?UserNotification
    {
        $user = $conversation->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $notification = (new UserNotification())
            ->setUser($user)
            ->setTitle('Nouvelle réponse du support')
            ->setBody(sprintf(
                'Le support a répondu à votre demande "%s".',
                (string) $conversation->getSubject(),
            ))
            ->setCategory('support')
            ->setPriority('medium')
            ->setIsRead(false)
            ->setActionLabel('Lire la réponse')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);

        return $notification;
    }

    public function notifyConversationResolved(SupportConversation $conversation): ?UserNotification
    {
        $user = $conversation->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $notification = (new UserNotification())
            ->setUser($user)
            ->setTitle('Demande résolue')
            ->setBody(sprintf(
                'Votre demande "%s" a été marquée comme résolue.',
                (string) $conversation->getSubject(),
            ))
            ->setCategory('support')
            ->setPriority('medium')
            ->setIsRead(false)
            ->setActionLabel('Consulter la demande')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);

        return $notification;
    }

    public function notifyForfaitExpiry(Forfait $forfait): ?UserNotification
    {
        $user = $forfait->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $expiresAt = $forfait->getExpiresAt() ?? new \DateTimeImmutable();
        $daysLeft = max(0, (int) (new \DateTimeImmutable())->diff($expiresAt)->days);

        $notification = (new UserNotification())
            ->setUser($user)
            ->setTitle('Échéance de votre forfait')
            ->setBody(sprintf(
                'Votre forfait "%s" expire dans %d jour%s. Renouvelez-le pour éviter toute interruption de service.',
                (string) $forfait->getLabel(),
                $daysLeft,
                1 !== $daysLeft ? 's' : '',
            ))
            ->setCategory('renewal')
            ->setPriority($daysLeft <= 2 ? 'high' : 'medium')
            ->setIsRead(false)
            ->setActionLabel('Renouveler mon forfait')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);

        return $notification;
    }

    public function notifyPaymentFailure(Payment $payment): ?UserNotification
    {
        $user = $payment->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $label = $payment->getForfait()?->getLabel() ?? 'abonnement';

        $notification = (new UserNotification())
            ->setUser($user)
            ->setTitle('Incident de paiement')
            ->setBody(sprintf(
                'Le paiement de %s € pour votre %s a échoué. Mettez à jour votre moyen de paiement pour éviter toute interruption.',
                number_format((float) $payment->getAmount(), 2, ',', ' '),
                $label,
            ))
            ->setCategory('payment')
            ->setPriority('high')
            ->setIsRead(false)
            ->setActionLabel('Mettre à jour le paiement')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);

        return $notification;
    }

    /**
     * @param array{id:string,text:string,validUntil:\DateTimeImmutable|null,recordedAt:\DateTimeImmutable} $disruption
     */
    public function notifyLineDisruption(LineSubscription $subscription, array $disruption): ?UserNotification
    {
        $user = $subscription->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $line = $subscription->getLine();
        $lineName = $line?->getName() ?? 'votre ligne';

        $notification = (new UserNotification())
            ->setUser($user)
            ->setLine($line)
            ->setTitle(sprintf('Perturbation — %s', $lineName))
            ->setBody($disruption['text'])
            ->setCategory('incident')
            ->setPriority('high')
            ->setIsRead(false)
            ->setActionLabel('Voir les détails')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);

        return $notification;
    }

    public function notifyConversationReopened(SupportConversation $conversation): ?UserNotification
    {
        $user = $conversation->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $notification = (new UserNotification())
            ->setUser($user)
            ->setTitle('Demande rouverte')
            ->setBody(sprintf(
                'Votre demande "%s" est à nouveau en cours de traitement.',
                (string) $conversation->getSubject(),
            ))
            ->setCategory('support')
            ->setPriority('low')
            ->setIsRead(false)
            ->setActionLabel('Suivre la demande')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);

        return $notification;
    }
}
