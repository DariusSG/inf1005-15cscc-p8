<?php

namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'app:migrate', description: 'Run database migrations')]
class MigrateCommand extends Command
{
    protected static $defaultName = 'app:migrate';

    protected function configure(): void
    {
        $this->setDescription('Run database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running migrations...');
        passthru('php ' . escapeshellarg(__DIR__.'/../../database/migrate.php'), $status);

        if ($status === 0) {
            $output->writeln('Migrations completed successfully.');
            return Command::SUCCESS;
        }

        $output->writeln('Migrations failed.');
        return Command::FAILURE;
    }
}