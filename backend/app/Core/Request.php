<?php

namespace App\Core;

class Request
{
    protected static array $context = [];
    protected static ?array $cachedBody = null;

    public static function body(): array
    {
        if (self::$cachedBody !== null) {
            return self::$cachedBody;
        }

        $input = file_get_contents("php://input");
        self::$cachedBody = json_decode($input, true) ?? [];

        return self::$cachedBody;
    }

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rtrim($uri, '/');

        return $uri === '' ? '/' : $uri;
    }

    public static function header(string $key): ?string
    {
        static $normalizedHeaders = null;

        if ($normalizedHeaders === null) {
            $normalizedHeaders = array_change_key_case(getallheaders(), CASE_LOWER);
        }

        return $normalizedHeaders[strtolower($key)] ?? null;
    }

    public static function setContext(string $key, mixed $value): void
    {
        self::$context[$key] = $value;
    }

    public static function context(string $key): mixed
    {
        return self::$context[$key] ?? null;
    }
}