<?php

namespace App\Core;

use App\Repositories\UserRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Handles database migrations and the Nextcloud-style installed flag.
 *
 * Used by:
 *   - AppServiceProvider::register()  — automatic on every HTTP boot
 *   - MigrateCommand                  — manual via ./occ app:migrate
 */
class Migrator
{
    private const MIGRATIONS_DIR = __DIR__ . '/../../database/migrations';

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Run all pending migrations.
     *
     * On first boot (app.installed === false):
     *   1. Creates the migrations tracking table.
     *   2. Runs every pending migration file.
     *   3. Seeds the default admin user.
     *   4. Writes 'installed = true' back into config/config.php.
     *
     * On subsequent boots the tracking table already exists and every
     * migration is already recorded, so the loop is a no-op.
     *
     * @param  callable|null $logger  fn(string $message) — receives progress lines
     * @return array{ran: string[], skipped: string[], errors: string[]}
     */
    public static function run(?callable $logger = null): array
    {
        $log = $logger ?? fn($msg) => null;

        $firstBoot = !Helpers::config('app.installed', false);

        // Ensure the migrations tracking table exists
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration')->unique();
                $table->timestamp('created_at')->useCurrent();
            });
            $log('Created migrations table.');
        }

        $ran     = [];
        $skipped = [];
        $errors  = [];

        foreach (glob(self::MIGRATIONS_DIR . '/*.php') as $file) {
            require_once $file;

            $class = 'Migration_' . basename($file, '.php');

            if (!class_exists($class)) {
                $errors[] = "Invalid migration class: {$class}";
                $log("ERROR: Invalid migration class: {$class}");
                continue;
            }

            if (Capsule::table('migrations')->where('migration', $class)->exists()) {
                $skipped[] = $class;
                $log("Skipping (already run): {$class}");
                continue;
            }

            try {
                (new $class())->up();
                Capsule::table('migrations')->insert(['migration' => $class]);
                $ran[] = $class;
                $log("Ran: {$class}");
            } catch (\Throwable $e) {
                $errors[] = "{$class}: " . $e->getMessage();
                $log("ERROR {$class}: " . $e->getMessage());
            }
        }

        // First-boot: seed admin + mark as installed
        if ($firstBoot) {
            self::seedAdmin($log);
            Helpers::writeConfig('app', 'installed', true);
            $log('Application marked as installed.');
        }

        return compact('ran', 'skipped', 'errors');
    }

    /**
     * Returns true if the migrations tracking table exists.
     */
    public static function hasMigrationsTable(): bool
    {
        try {
            return Capsule::schema()->hasTable('migrations');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Returns the list of already-applied migration names.
     */
    public static function appliedMigrations(): array
    {
        if (!self::hasMigrationsTable()) {
            return [];
        }
        return Capsule::table('migrations')->pluck('migration')->toArray();
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Seed the default admin user.
     * Credentials come from ADMIN_EMAIL / ADMIN_PASS env vars only —
     * these secrets must never be stored in config/config.php.
     */
    private static function seedAdmin(callable $log): void
    {
        $email    = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
        $password = $_ENV['ADMIN_PASS']  ?? 'changeme';

        if (UserRepository::findByEmail($email)) {
            $log("Admin user already exists: {$email}");
            return;
        }

        $hashed = password_hash($password, PASSWORD_ARGON2ID);
        UserRepository::create($email, $hashed, 'admin', 'Admin');
        $log("Default admin created: {$email}");
    }
}
