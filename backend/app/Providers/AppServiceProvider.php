<?php

namespace App\Providers;

use App\Core\Container;
use App\Core\Helpers;
use App\Config\Database;
use App\Services\AuthService;
use App\Services\TokenService;
use App\Services\UserService;
use App\Services\MailService;
use App\Services\VerificationService;
use App\Repositories\UserRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

class AppServiceProvider
{
    public static function register(): void
    {
        Database::init();

        // ── Nextcloud-style installed flag ────────────────────────────────
        // Always run pending migrations on every boot (safe: migration table
        // tracks what has already been applied).  On the very first boot,
        // also seed the default admin user and write 'installed = true' into
        // config/config.php so subsequent boots skip the seeding step.
        self::runMigrations();

        Container::bind('TokenService',        fn() => new TokenService(),                                        true);
        Container::bind('MailService',         fn() => new MailService(),                                         true);
        Container::bind('VerificationService', fn() => new VerificationService(Container::resolve('MailService')), true);
        Container::bind('AuthService',         fn() => new AuthService(),                                         true);
        Container::bind('UserService',         fn() => new UserService(),                                         true);
    }

    /**
     * Run any pending database migrations.
     *
     * On the very first run (app.installed === false in config/config.php):
     *   1. Creates the migrations tracking table.
     *   2. Runs all pending migration files.
     *   3. Seeds the default admin user (ADMIN_EMAIL / ADMIN_PASS env vars).
     *   4. Writes 'installed = true' back into config/config.php.
     *
     * On subsequent runs the migrations table already exists and every
     * migration file is already recorded, so the loop is a no-op.
     */
    private static function runMigrations(): void
    {
        $firstBoot = !Helpers::config('app.installed', false);

        // Ensure the migrations tracking table exists
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration')->unique();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        $anyRan = false;
        foreach (glob(__DIR__ . '/../../database/migrations/*.php') as $file) {
            require_once $file;

            $class = basename($file, '.php');

            if (Capsule::table('migrations')->where('migration', $class)->exists()) {
                continue; // already applied
            }

            try {
                (new $class())->up();
                Capsule::table('migrations')->insert(['migration' => $class]);
                $anyRan = true;
            } catch (\Throwable $e) {
                error_log("Migration {$class} failed: " . $e->getMessage());
            }
        }

        // First-boot: seed admin + mark as installed
        if ($firstBoot) {
            self::seedAdmin();
            // Persist installed = true into config/config.php
            Helpers::writeConfig('app', 'installed', true);
        }
    }

    /**
     * Seed the default admin user from ADMIN_EMAIL / ADMIN_PASS env vars.
     * Idempotent: skips if the user already exists.
     */
    private static function seedAdmin(): void
    {
        $email    = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
        $password = $_ENV['ADMIN_PASS']  ?? 'changeme';

        if (UserRepository::findByEmail($email)) {
            return;
        }

        $hashed = password_hash($password, PASSWORD_ARGON2ID);
        $admin  = UserRepository::create($email, $hashed, 'admin', 'Admin');
        // role is already set via create(), but ensure it's persisted
        if ($admin->role !== 'admin') {
            $admin->role = 'admin';
            $admin->save();
        }
    }
}