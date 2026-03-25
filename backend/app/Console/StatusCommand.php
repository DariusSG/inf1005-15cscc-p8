<?php

namespace App\Console;

use App\Core\Helpers;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'app:status', description: 'Show application status')]
class StatusCommand extends Command
{
    protected static $defaultName = 'app:status';

    protected function configure(): void
    {
        $this->setDescription('Show application status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Application Status');
        $output->writeln('------------------');

        $output->writeln('Version: ' . (Helpers::config('app.version', 'dev')));
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
            $output->writeln('Database: OK');
        } catch (\Exception $e) {
            $output->writeln('Database: FAILED');
            $output->writeln('Error: '. $e->getMessage());
        }

        return Command::SUCCESS;
    }
}