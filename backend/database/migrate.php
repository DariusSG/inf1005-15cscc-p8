<?php

require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use Illuminate\Database\Capsule\Manager as Capsule;

/*
|--------------------------------------------------------------------------
| Load ENV
|--------------------------------------------------------------------------
*/

$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

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

    $class = basename($file, ".php");

    // Check if migration was already run
    $alreadyRun = Capsule::table('migrations')->where('migration', $class)->exists();
    if ($alreadyRun) {
        echo "Skipping already run migration: $class\n";
        continue;
    }

    $migration = new $class();

    try {
        $migration->up();
        Capsule::table('migrations')->insert(['migration' => $class]);
        echo "Migration $class completed.\n";
    } catch (\Exception $e) {
        echo "Migration $class failed: ".$e->getMessage()."\n";
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
    $hashed = password_hash($adminPassword, PASSWORD_BCRYPT);
    $admin = UserRepository::create($adminEmail, $hashed);
    $admin->role = 'admin';
    $admin->save();
    echo "Default admin user created: $adminEmail\n";
} else {
    echo "Admin user already exists: $adminEmail\n";
}