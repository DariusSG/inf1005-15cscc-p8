<?php

require __DIR__.'/../vendor/autoload.php';

use App\Config\Database;
use App\Core\Helpers;
use App\Core\Migrator;
use Illuminate\Database\Capsule\Manager as Capsule;

// Initialise DB (no migration run — status is read-only)
try {
    Database::init();
    $dbConnected = true;
    $dbError     = null;
} catch (\Throwable $e) {
    $dbConnected = false;
    $dbError     = $e->getMessage();
}

$installed        = Helpers::config('app.installed', false);
$hasMigrTable     = $dbConnected && Migrator::hasMigrationsTable();
$appliedMigrations = $hasMigrTable ? Migrator::appliedMigrations() : [];

$status = [
    'app'       => 'SITizen API',
    'version'   => Helpers::config('app.version', 'dev'),
    'installed' => $installed,
    'database'  => [
        'connected'          => $dbConnected,
        'error'              => $dbError,
        'migrations_table'   => $hasMigrTable,
        'applied_migrations' => $appliedMigrations,
    ],
];

// HTTP status reflects installation state
if (!$installed || !$dbConnected) {
    http_response_code(503);
}

header('Content-Type: application/json');
echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
