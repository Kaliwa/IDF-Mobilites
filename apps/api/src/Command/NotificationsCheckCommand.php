<?php

namespace App\Command;

use App\Entity\UserNotification;
use App\Service\MessagingRealtimePublisher;
use App\Service\NotificationsChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notifications:check',
    description: 'Check pending forfait expiries and failed payments, create and push notifications',
)]
final class NotificationsCheckCommand extends Command
{
    public function __construct(
        private readonly NotificationsChecker $checker,
        private readonly MessagingRealtimePublisher $publisher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $total = 0;

        foreach ($this->checker->getAllEligibleUsers() as $user) {
            $notifications = $this->checker->checkUser($user);

            foreach ($notifications as $notification) {
                $this->publisher->publishNotificationUpdate(
                    $notification,
                    $this->serialize($notification),
                );
            }

            $count = count($notifications);
            if ($count > 0) {
                $io->writeln(sprintf(
                    '  %s → %d notification(s)',
                    (string) $user->getEmail(),
                    $count,
                ));
            }

            $total += $count;
        }

        $io->success(sprintf('%d notification(s) created and published.', $total));

        return Command::SUCCESS;
    }

    /**
     * @return array{id:int,title:string,body:string,category:string,priority:string,isRead:bool,createdAt:string|null,line:array{id:int,code:string,name:string}|null,actionLabel:string|null}
     */
    private function serialize(UserNotification $notification): array
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
}
