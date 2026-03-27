<?php

namespace App;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Core\Helpers;

class Database
{
    public static function init()
    {   
        self::validateConfig();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => Helpers::config('database.driver', 'mysql'),
            'host'      => Helpers::config('database.host'),
            'database'  => Helpers::config('database.name'),
            'username'  => Helpers::config('database.username'),
            'password'  => Helpers::config('database.password'),
            'charset'   => Helpers::config('database.charset', 'utf8mb4'),
            'collation' => Helpers::config('database.collation', 'utf8mb4_unicode_ci'),
            'prefix'    => Helpers::config('database.prefix', ''),
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    private static function validateConfig(): void
    {
        $required = [
            'database.host' => 'DB_HOST',
            'database.name' => 'DB_NAME',
            'database.username' => 'DB_USER',
            'database.password' => 'DB_PASS',
        ];

        $missing = [];
        foreach ($required as $key => $envVar) {
            $value = Helpers::config($key);
            if (empty($value)) {
                $missing[] = $envVar;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing)
            );
        }
    }


    public static function connection()
    {
        return Capsule::connection();
    }
}

class JwtConfig
{
    public static function keys(): array
    {
        return Helpers::config('jwt.keys') ?: [
            'v1' => 'secret1',
            'v2' => 'secret2',
        ];
    }

    public static function currentKid(): string
    {
        return Helpers::config('jwt.current_kid') ?: 'v1';
    }

    public static function secret(string $kid): string
    {
        $keys = self::keys();
        if (!isset($keys[$kid])) {
            throw new \Exception("Invalid key id: $kid");
        }
        return $keys[$kid];
    }

    public static function expire(): int
    {
        return (int) Helpers::config('jwt.expire', 3600);
    }

    public static function access_ttl(): int
    {
        return (int) Helpers::config('jwt.access_ttl', 900);
    }

    public static function refresh_ttl(): int
    {
        return (int) Helpers::config('jwt.refresh_ttl', 604800);
    }
}

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