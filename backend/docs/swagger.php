<?php
require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

// Scan application sources including DTO schemas
$openapi = (new Generator())->generate([
    __DIR__ . '/../app/Controllers',
    __DIR__ . '/../app/Repositories',
    __DIR__ . '/../app/DTOs',
]);

header('Content-Type: application/json');
echo $openapi->toJson();