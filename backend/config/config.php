<?php

return [

    'app' => [
        'version'   => '1.0.0',
        'url'       => $_ENV['APP_URL'] ?? 'https://example.com',
        'installed' => false,   // set to true automatically after first successful migration
    ],

    'cors' => [
        'allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? '',
    ],

    'database' => [
        'driver'    => $_ENV['DB_DRIVER']    ?? 'mysql',
        'host'      => $_ENV['DB_HOST']      ?? 'localhost',
        'name'      => $_ENV['DB_NAME']      ?? '',
        'username'  => $_ENV['DB_USER']      ?? '',
        'password'  => $_ENV['DB_PASS']      ?? '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],

    'jwt' => [
        'keys' => [
            'v1' => $_ENV['JWT_SECRET_V1'] ?? 'changeme_v1',
            'v2' => $_ENV['JWT_SECRET_V2'] ?? 'changeme_v2',
        ],
        'current_kid' => $_ENV['JWT_CURRENT_KID'] ?? 'v1',
        'expire'      => (int) ($_ENV['JWT_EXPIRE']      ?? 3600),
        'access_ttl'  => (int) ($_ENV['JWT_ACCESS_TTL']  ?? 900),
        'refresh_ttl' => (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800),
    ],

    'mail' => [
        'host'       => $_ENV['MAIL_HOST']       ?? 'smtp.mailtrap.io',
        'port'       => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username'   => $_ENV['MAIL_USERNAME']   ?? '',
        'password'   => $_ENV['MAIL_PASSWORD']   ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@example.com',
        'from_name'  => $_ENV['MAIL_FROM_NAME']  ?? 'App',
        'verify_ttl' => (int) ($_ENV['MAIL_VERIFY_TTL'] ?? 86400),
    ],

];