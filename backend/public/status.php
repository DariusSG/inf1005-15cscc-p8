<?php
require __DIR__.'/../vendor/autoload.php';

use App\Config\Database;
use App\Core\Helpers;
use Illuminate\Database\Capsule\Manager as Capsule;

Database::init();

// Basic status response
$status = [
    'app' => 'My Backend',
    'version' => Helpers::config('app.version', 'dev'),
    'db_connected' => false,
];

// test DB connection
try {
    Capsule::connection()->getPdo();
    $status['db_connected'] = true;
} catch (\Exception $e) {
    $status['db_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($status, JSON_PRETTY_PRINT);