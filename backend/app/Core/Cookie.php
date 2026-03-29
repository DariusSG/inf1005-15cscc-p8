<?php

namespace App\Core;

class Cookie
{
    /**
     * Centralized defaults to ensure consistent security across all methods.
     */
    private static function getDefaults(): array
    {
        return [
            'path'     => '/',
            'domain'   => Helpers::config('app.domain', ''),
            'secure'   => Helpers::config('app.env') === 'prod',
            'httponly' => true,
            'samesite' => 'Strict',
        ];
    }

    /**
     * The master set method. All other setters flow through here.
     */
    public static function set(string $name, string $value, array $options = []): void
    {
        // Merge provided options into the secure defaults
        $params = array_merge(self::getDefaults(), $options);

        // setcookie accepts the options array in PHP 7.3+
        setcookie($name, $value, $params);

        // Update superglobal so it's available in the current request immediately
        $_COOKIE[$name] = $value;
    }

    /**
     * Set a persistent cookie using days or seconds (TTL).
     */
    public static function setPersistent(string $name, string $value, int $days = 7, array $options = []): void
    {
        $options['expires'] = time() + ($days * 86400);
        self::set($name, $value, $options);
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function delete(string $name): void
    {
        if (isset($_COOKIE[$name])) {
            self::set($name, '', ['expires' => time() - 3600]);
            unset($_COOKIE[$name]);
        }
    }

    /**
     * Special case: Auth/Refresh tokens should be extremely locked down.
     */
    public static function setRefreshToken(string $token, int $ttl): void
    {
        self::set('refresh_token', $token, [
            'expires'  => time() + $ttl,
            'samesite' => 'Strict',
            'httponly' => true
        ]);
    }

    /**
     * Special case: Preferences/UI settings that JavaScript needs to read.
     */
    public static function setPreference(string $name, string $value, int $days = 365): void
    {
        self::set($name, $value, [
            'expires'  => time() + ($days * 86400),
            'httponly' => false, // JS can read this
            'samesite' => 'Lax'   // Lax is usually better for UI state
        ]);
    }

    public static function clear(): void
    {
        foreach (array_keys($_COOKIE) as $name) {
            self::delete($name);
        }
    }

    public static function all(): array
    {
        return $_COOKIE;
    }
}