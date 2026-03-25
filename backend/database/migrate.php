<?php

require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use Illuminate\Database\Capsule\Manager as Capsule;

/*
|--------------------------------------------------------------------------
| Init DB
|--------------------------------------------------------------------------
*/


Database::init();

/*
|--------------------------------------------------------------------------
| Create migrations table if not exists
|--------------------------------------------------------------------------
*/

if (!Capsule::schema()->hasTable('migrations')) {
    Capsule::schema()->create('migrations', function ($table) {
        $table->increments('id');
        $table->string('migration')->unique();
        $table->timestamp('created_at')->useCurrent();
    });
}

/*
|--------------------------------------------------------------------------
| Run migrations
|--------------------------------------------------------------------------
*/

foreach (glob(__DIR__."/migrations/*.php") as $file) {
    require $file;

    $filename = basename($file, ".php");

    // Convert filename to valid PHP class name
    // Example: '001_initial_schema' -> 'Migration_001_initial_schema'
    $class = 'Migration_' . $filename;

    if (!class_exists($class)) {
        echo "Skipping invalid migration class: $class\n";
        continue;
    }

    $alreadyRun = Capsule::table('migrations')->where('migration', $filename)->exists();
    if ($alreadyRun) {
        echo "Skipping already run migration: $filename\n";
        continue;
    }

    $migration = new $class();

    try {
        $migration->up();
        Capsule::table('migrations')->insert(['migration' => $filename]);
        echo "Migration $filename completed.\n";
    } catch (\Exception $e) {
        echo "Migration $filename failed: ".$e->getMessage()."\n";
    }
}

/*
|--------------------------------------------------------------------------
| Seed default admin user
|--------------------------------------------------------------------------
*/

use App\Repositories\UserRepository;

$adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
$adminPassword = $_ENV['ADMIN_PASS'] ?? 'password';

$existingAdmin = UserRepository::findByEmail($adminEmail);

if (!$existingAdmin) {
    $hashed = password_hash($adminPassword, PASSWORD_ARGON2ID);
    $admin = UserRepository::create($adminEmail, $hashed);
    $admin->role = 'admin';
    $admin->save();
    echo "Default admin user created: $adminEmail\n";
} else {
    echo "Admin user already exists: $adminEmail\n";
}