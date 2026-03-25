<?php

/**
 * Application configuration — edit this file directly (Nextcloud-style).
 *
 * DO NOT use $_ENV here (except for secrets that must never be committed,
 * such as the initial admin password which is seeded from ADMIN_PASS env var).
 *
 * After the first successful migration run, 'app.installed' is automatically
 * set to true by AppServiceProvider and written back to this file.
 */

return [

    'app' => [
        'version'   => '1.0.0',
        'url'       => 'http://localhost',
        'installed' => false,   // written to true automatically on first boot
    ],

    'cors' => [
        // Comma-separated list of allowed origins.
        // Empty string = allow all (development only — restrict in production).
        'allowed_origins' => '',
    ],

    'database' => [
        'driver'    => 'mysql',
        'host'      => 'db',
        'name'      => 'inf1005_local',
        'username'  => 'sitizen',
        'password'  => 'changeme',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],

    'jwt' => [
        // Generate strong random secrets before deploying to production.
        // Minimum 32 characters recommended.
        'keys' => [
            'v1' => 'changeme_jwt_secret_v1_replace_me',
            'v2' => 'changeme_jwt_secret_v2_replace_me',
        ],
        'current_kid' => 'v1',
        'expire'      => 3600,
        'access_ttl'  => 900,      // 15 minutes
        'refresh_ttl' => 604800,   // 7 days
    ],

    'mail' => [
        'host'       => 'smtp.mailtrap.io',
        'port'       => 587,
        'username'   => '',
        'password'   => '',
        'encryption' => 'tls',
        'from_email' => 'noreply@example.com',
        'from_name'  => 'SITizen',
        'verify_ttl' => 86400,   // 24 hours
    ],

];
