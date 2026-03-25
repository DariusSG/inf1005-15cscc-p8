<?php

require __DIR__ . '/../vendor/autoload.php';

// Load .env if present (tests may run without a real DB)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}
