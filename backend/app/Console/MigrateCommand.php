<?php

namespace App\Console;

use App\Config\Database;
use App\Core\Helpers;
use App\Core\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migration:run', description: 'Run pending database migrations')]
class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Run pending database migrations and seed the default admin user on first run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialise DB connection (no HTTP bootstrap needed)
        try {
            Database::init();
        } catch (\Throwable $e) {
            $output->writeln('<error>Database connection failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('Running migrations…');

        $result = Migrator::run(fn($msg) => $output->writeln('  ' . $msg));

        if (!empty($result['ran'])) {
            // If we just ran migrations for the first time, mark as installed
            if (!Helpers::config('app.installed')) {
                Helpers::writeConfig('app.installed', true);
                $output->writeln('<info>Application marked as installed.</info>');
            }
            $output->writeln('<info>Applied ' . count($result['ran']) . ' migration(s).</info>');
        } else {
            $output->writeln('<info>No pending migrations.</info>');
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $err) {
                $output->writeln('<error>' . $err . '</error>');
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
