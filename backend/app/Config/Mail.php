<?php

namespace App\Config;

use App\Core\Helpers;

class Mail
{
    public static function config(): array
    {
        return [
            'host'       => Helpers::config('mail.host', 'smtp.mailtrap.io'),
            'port'       => (int) Helpers::config('mail.port', 587),
            'username'   => Helpers::config('mail.username', ''),
            'password'   => Helpers::config('mail.password', ''),
            'encryption' => Helpers::config('mail.encryption', 'tls'),
            'from_email' => Helpers::config('mail.from_email', 'noreply@example.com'),
            'from_name'  => Helpers::config('mail.from_name', 'App'),
        ];
    }

    public static function appUrl(): string
    {
        return rtrim(Helpers::config('app.url', ''), '/');
    }

    public static function verifyTtl(): int
    {
        return (int) Helpers::config('mail.verify_ttl', 86400);
    }
}