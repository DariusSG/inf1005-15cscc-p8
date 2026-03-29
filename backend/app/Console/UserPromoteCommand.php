<?php

namespace App\Console;

use App\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'user:promote', description: 'Promote a user to admin')]
class UserPromoteCommand extends Command
{
    protected static $defaultName = 'app:user:promote';

    protected function configure(): void
    {
        $this
            ->setDescription('Promote a user to admin')
            ->addArgument('email', InputArgument::REQUIRED, 'Email of user to promote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $io->error("User not found: {$email}");
            return Command::FAILURE;
        }

        $user->role = 'admin';
        $user->save();

        $io->success("User {$email} promoted to admin.");
        return Command::SUCCESS;
    }
}