<?php

namespace App\Middleware;

use App\Core\Helpers;

class CorsMiddleware
{
    private static function getAllowedOrigins(): array
    {
        $origins = explode(',', Helpers::config('cors.allowed_origins', ''));
        return array_filter(array_map('trim', $origins));
    }

    private static function isOriginAllowed(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = self::getAllowedOrigins();

        // Allow if no origins configured (dev mode) or origin is in allowed list
        return empty($allowed) || in_array($origin, $allowed, true);
    }


    public static function handle()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (self::isOriginAllowed()) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }
 
        header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        header("Access-Control-Max-Age: 86400"); // 24 hours

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }
}