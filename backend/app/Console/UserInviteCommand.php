<?php

namespace App\Console;

use App\Models\EmailVerification;
use App\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'user:invite', description: 'Generate an invite token for a new user')]
class UserInviteCommand extends Command
{
    protected static $defaultName = 'app:user:invite';

    protected function configure(): void
    {
        $this
            ->setDescription('Generate an invite token for a new user')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address to invite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $io->error("User with email {$email} already exists.");
            return Command::FAILURE;
        }

        // Generate a secure random token (hex)
        $token = bin2hex(random_bytes(32));

        // Create EmailVerification record
        EmailVerification::create([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + (86400)), // default 24h
        ]);

        $io->success("Invite token created for {$email}");
        $io->writeln("Token: {$token}");
        $io->writeln("Use this token to complete registration.");

        return Command::SUCCESS;
    }
}