<?php

namespace App\Config;

class Mail
{
    public static function config(): array
    {
        return [
            'host'       => $_ENV['MAIL_HOST']       ?? 'smtp.mailtrap.io',
            'port'       => (int)($_ENV['MAIL_PORT'] ?? 587),
            'username'   => $_ENV['MAIL_USERNAME']   ?? '',
            'password'   => $_ENV['MAIL_PASSWORD']   ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@sitizen.app',
            'from_name'  => $_ENV['MAIL_FROM_NAME']  ?? 'SITizen',
        ];
    }

    /** Base URL of the frontend, e.g. https://sitizen.app */
    public static function appUrl(): string
    {
        return rtrim($_ENV['APP_URL'] ?? 'http://localhost:5173', '/');
    }

    /** TTL in seconds for the invite link (default 24 h) */
    public static function verifyTtl(): int
    {
        return (int)($_ENV['MAIL_VERIFY_TTL'] ?? 86400);
    }
}