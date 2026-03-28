<?php

namespace App\Console;

use App\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(name: 'user:create', description: 'Create a new user')]
class UserCreateCommand extends Command
{
    protected static $defaultName = 'app:user:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new user')
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the new user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the user')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Full name of the user')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'User role (e.g., admin or student)', 'student');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email    = $input->getArgument('email');
        $password = $input->getArgument('password');
        $name     = $input->getOption('name') ?? '';
        $role     = $input->getOption('role');

        $hashed = password_hash($password, PASSWORD_ARGON2ID);

        $user = User::create([
            'email'    => $email,
            'password' => $hashed,
            'name'     => $name,
            'role'     => $role,
        ]);

        $io->success("User {$user->email} created successfully (ID {$user->id}).");

        return Command::SUCCESS;
    }
}