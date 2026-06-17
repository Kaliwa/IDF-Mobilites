<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:users:create-support',
    description: 'Create or update a support/admin backoffice account.',
)]
final class CreateSupportUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address of the account')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the account')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Grant ROLE_ADMIN instead of ROLE_SUPPORT')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Update password if account already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');
        $isAdmin = $input->getOption('admin');
        $allowUpdate = $input->getOption('update');

        $role = $isAdmin ? 'ROLE_ADMIN' : 'ROLE_SUPPORT';

        $repo = $this->entityManager->getRepository(User::class);
        $existing = $repo->findOneBy(['email' => $email]);

        if ($existing !== null) {
            if (!$allowUpdate) {
                $io->error(sprintf('Account "%s" already exists. Use --update to reset the password.', $email));
                return Command::FAILURE;
            }

            $hashed = $this->passwordHasher->hashPassword($existing, $plainPassword);
            $existing->setPassword($hashed);

            if (!in_array($role, $existing->getRoles(), true)) {
                $existing->setRoles(array_unique([...$existing->getRoles(), $role]));
            }

            $this->entityManager->flush();
            $io->success(sprintf('Account "%s" updated (roles: %s).', $email, implode(', ', $existing->getRoles())));
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Account "%s" created with role %s.', $email, $role));
        return Command::SUCCESS;
    }
}
