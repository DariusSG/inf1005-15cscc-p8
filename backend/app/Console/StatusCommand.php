<?php

namespace App\Console;

use App\Config\Database;
use App\Core\Helpers;
use App\Core\Migrator;
use Illuminate\Database\Capsule\Manager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:status', description: 'Show application status')]
class StatusCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Show application version, installation state, and database status.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>SITizen API — Application Status</info>');
        $output->writeln(str_repeat('─', 40));

        // ── Version ───────────────────────────────────────────────────────
        $output->writeln('Version   : ' . Helpers::config('app.version', 'dev'));

        // ── Installed flag ────────────────────────────────────────────────
        $installed = Helpers::config('app.installed', false);
        $output->writeln('Installed : ' . ($installed ? '<info>yes</info>' : '<comment>no</comment>'));

        // ── Database ──────────────────────────────────────────────────────
        try {
            Database::init();
            Manager::connection()->getPdo();
            $output->writeln('Database  : <info>connected</info>');
        } catch (Throwable $e) {
            $output->writeln('Database  : <error>FAILED — ' . $e->getMessage() . '</error>');
            $output->writeln('');
            return Command::FAILURE;
        }

        // ── Migrations table ──────────────────────────────────────────────
        $hasMigrationTable = Migrator::hasMigrationsTable();
        $output->writeln('Migrations table : ' . ($hasMigrationTable ? '<info>exists</info>' : '<comment>missing</comment>'));

        if ($hasMigrationTable) {
            $applied = Migrator::appliedMigrations();
            $output->writeln('Applied migrations (' . count($applied) . '):');
            foreach ($applied as $m) {
                $output->writeln('  ✔ ' . $m);
            }
        }

        $output->writeln('');
        return Command::SUCCESS;
    }
}
