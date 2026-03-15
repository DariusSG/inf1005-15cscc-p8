<?php
require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

// Scan the `app/Controllers` folder
$openapi = (new Generator())->generate([__DIR__ . '/../app/Controllers']);

header('Content-Type: application/json');
echo $openapi->toJson();