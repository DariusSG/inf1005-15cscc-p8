<?php

namespace App\Console;

use App\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'user:reset-password', description: 'Reset a user\'s password')]
class UserResetPasswordCommand extends Command
{
    protected static $defaultName = 'app:user:reset-password';

    protected function configure(): void
    {
        $this
            ->setDescription('Reset a user\'s password')
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'New password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email    = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $io->error("User not found: {$email}");
            return Command::FAILURE;
        }

        $user->password = password_hash($password, PASSWORD_ARGON2ID);
        $user->save();

        $io->success("Password reset for {$email}.");

        return Command::SUCCESS;
    }
}