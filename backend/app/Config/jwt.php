<?php

namespace App\Config;

use App\Core\Helpers;

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