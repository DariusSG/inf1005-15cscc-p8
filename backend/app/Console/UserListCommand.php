<?php

namespace App\Console;

use App\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(name: 'user:list', description: 'List all users')]
class UserListCommand extends Command
{
    protected static $defaultName = 'app:user:list';

    protected function configure(): void
    {
        $this->setDescription('List all users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = User::all(['id', 'email', 'name', 'role']);

        $table = new Table($output);
        $table->setHeaders(['ID', 'Email', 'Name', 'Role']);

        foreach ($users as $user) {
            $table->addRow([$user->id, $user->email, $user->name, $user->role]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}