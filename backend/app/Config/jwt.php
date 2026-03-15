<?php

namespace App\Config;

class JwtConfig
{
    public static function keys()
    {
        return [
            "v1" => $_ENV['JWT_SECRET_V1'] ?? 'secret1',
            "v2" => $_ENV['JWT_SECRET_V2'] ?? 'secret2'
        ];
    }

    public static function currentKid()
    {
        return $_ENV['JWT_CURRENT_KID'] ?? 'v1';
    }

    public static function secret(string $kid)
    {
        $keys = self::keys();
        if (!isset($keys[$kid])) {
            throw new \Exception("Invalid key id");
        }
        return $keys[$kid];
    }

    public static function expire()
    {
        return $_ENV['JWT_EXPIRE'] ?? 3600;
    }

    public static function access_ttl()
    {
        return $_ENV['JWT_TTL_ACCESS'] ?? 900;
    }

    public static function refresh_ttl()
    {
        return $_ENV['JWT_TTL_REFRESH'] ?? 604800;
    }
}