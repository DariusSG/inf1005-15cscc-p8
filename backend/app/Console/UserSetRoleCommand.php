<?php

namespace App\Console;

use App\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:set-role', description: 'Set user role')]
class UserSetRoleCommand extends Command
{
    protected static $defaultName = 'app:user:set-role';

    protected function configure(): void
    {
        $this
            ->setDescription('Set user role')
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to set (e.g., student|admin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $role  = $input->getArgument('role');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $io->error("User not found: {$email}");
            return Command::FAILURE;
        }

        $user->role = $role;
        $user->save();

        $io->success("User {$email} role set to {$role}.");
        return Command::SUCCESS;
    }
}