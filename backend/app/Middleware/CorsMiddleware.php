<?php

namespace App\Middleware;

use App\Core\Helpers;

class CorsMiddleware implements Middleware
{
    private static function getAllowedOrigins(): array
    {
        $rawConfig = Helpers::config('cors.allowed_origins', '');
        $origins = explode(',', $rawConfig);

        return array_filter(array_map(function($o) {
            return rtrim(trim($o), '/');
        }, $origins));
    }

    private static function isOriginAllowed(): bool
    {
        $origin = rtrim($_SERVER['HTTP_ORIGIN'] ?? '', '/');
        
        if ($origin === '') return false;

        $allowed = self::getAllowedOrigins();

        return in_array($origin, $allowed, true);
    }


    public static function handle(string ...$args): void
    {
        $origin = rtrim($_SERVER['HTTP_ORIGIN'] ?? '', '/');

        if ($origin !== '' && self::isOriginAllowed()) {
            // Dynamically echo the matched origin
            header("Access-Control-Allow-Origin: $origin");
            header('Vary: Origin'); // Crucial for CDNs/Proxies
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Max-Age: 86400'); // 24 hours

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit(0);
        }
    }
}