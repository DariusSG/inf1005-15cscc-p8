<?php

namespace App\Core;

use App\Repositories\UserRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;

class Migrator
{
    private const string MIGRATIONS_DIR = __DIR__ . '/../../database/migrations';

    public static function run(?callable $logger = null): array
    {
        $log = $logger ?? fn($msg) => null;
        $isFirstBoot = !Helpers::config('app.installed', false);

        // Ensure tracking table exists
        self::ensureMigrationsTable($log);

        $ran = [];
        $skipped = [];
        $errors = [];

        // Get and sort migration files (ensures 001 runs before 002)
        $files = glob(self::MIGRATIONS_DIR . '/*.php');
        if ($files === false) {
            return ['ran' => [], 'skipped' => [], 'errors' => ['Could not read migrations directory']];
        }
        sort($files);

        foreach ($files as $file) {
            require_once $file;
            $className = 'Migration_' . basename($file, '.php');

            if (!class_exists($className)) {
                $errors[] = "Class $className missing in $file";
                continue;
            }

            // Check if already run
            if (Capsule::table('migrations')->where('migration', $className)->exists()) {
                $skipped[] = $className;
                continue;
            }

            try {
                // Wrap in transaction: ensures the 'up' logic and migration log stay in sync
                Capsule::connection()->transaction(function () use ($className, &$ran) {
                    new $className()->up();
                    Capsule::table('migrations')->insert([
                        'migration' => $className,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $ran[] = $className;
                });
                $log("Ran: $className");
            } catch (Throwable $e) {
                $errors[] = "$className: " . $e->getMessage();
                $log("ERROR $className: " . $e->getMessage());
                break; // Stop execution on failure to prevent dependency issues
            }
        }

        // Finalize First Boot
        if ($isFirstBoot && empty($errors)) {
            self::seedAdmin($log);
            Helpers::writeConfig('app', 'installed', true);
            $log('Application marked as installed.');
        }

        return compact('ran', 'skipped', 'errors');
    }

    private static function ensureMigrationsTable(callable $log): void
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration', 191)->unique();
                $table->timestamp('created_at')->nullable();
            });
            $log('Created migrations table.');
        }
    }

    public static function appliedMigrations(): array
    {
        return Capsule::schema()->hasTable('migrations')
            ? Capsule::table('migrations')->pluck('migration')->toArray()
            : [];
    }

    private static function seedAdmin(callable $log): void
    {
        $email = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
        $pass  = $_ENV['ADMIN_PASS']  ?? 'changeme';

        if (UserRepository::findByEmail($email)) {
            $log("Admin already exists: $email");
            return;
        }

        UserRepository::create(
            $email,
            password_hash($pass, PASSWORD_ARGON2ID),
            'admin',
            'Admin'
        );
        $log("Default admin created: $email");
    }
}