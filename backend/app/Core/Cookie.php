<?php

namespace App\Core;

class Cookie
{
    /**
     * Default cookie options
     */
    private static $defaults = [
        'path' => '/',
        'samesite' => 'Strict',
        'httponly' => true,
    ];

    /**
     * Set a cookie with secure defaults
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param array $options Optional: path, domain, expires, secure, httponly, samesite
     */
    public static function set(string $name, string $value, array $options = []): void
    {
        $options = array_merge(self::$defaults, $options);
        
        // Auto-detect secure flag for production
        if (!isset($options['secure'])) {
            $options['secure'] = self::isProduction();
        }

        // Set expiration in seconds if not provided
        if (!isset($options['expires'])) {
            $options['expires'] = 0; // Session cookie
        }

        setcookie($name, $value, $options);
    }

    /**
     * Set a persistent cookie (expires after N days)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $days Days until expiration
     * @param array $options Additional cookie options
     */
    public static function setPersistent(string $name, string $value, int $days = 7, array $options = []): void
    {
        $options['expires'] = time() + ($days * 86400);
        self::set($name, $value, $options);
    }

    /**
     * Get a cookie value
     *
     * @param string $name Cookie name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Check if cookie exists
     *
     * @param string $name Cookie name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete a cookie
     *
     * @param string $name Cookie name
     */
    public static function delete(string $name): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => self::isProduction(),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        unset($_COOKIE[$name]);
    }

    /**
     * Clear all cookies
     */
    public static function clear(): void
    {
        foreach ($_COOKIE as $name => $value) {
            self::delete($name);
        }
    }

    /**
     * Check if running in production environment
     *
     * @return bool
     */
    private static function isProduction(): bool
    {
        return !empty($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production';
    }

    /**
     * Set a cookie with custom TTL (time-to-live in seconds)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $ttl Time to live in seconds
     * @param array $options Additional cookie options
     */
    public static function setWithTtl(string $name, string $value, int $ttl, array $options = []): void
    {
        $options['expires'] = time() + $ttl;
        self::set($name, $value, $options);
    }

    /**
     * Set authentication cookie (refresh token)
     *
     * @param string $token Token value
     * @param int $ttl Time to live in seconds
     */
    public static function setRefreshToken(string $token, int $ttl): void
    {
        self::set('refresh_token', $token, [
            'expires' => time() + $ttl,
            'path' => '/',
            'secure' => self::isProduction(),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    /**
     * Get refresh token from cookie
     *
     * @return string|null
     */
    public static function getRefreshToken(): ?string
    {
        return self::get('refresh_token');
    }

    /**
     * Delete refresh token cookie
     */
    public static function deleteRefreshToken(): void
    {
        self::delete('refresh_token');
    }

    /**
     * Set a preference/settings cookie (not httponly, can be accessed by JS)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $days Days until expiration
     */
    public static function setPreference(string $name, string $value, int $days = 365, array $options = []): void
    {
        $options['httponly'] = false; // Allow JS access for preferences
        $options['expires'] = time() + ($days * 86400);
        self::set($name, $value, $options);
    }

    /**
     * Get all cookies as array
     *
     * @return array
     */
    public static function all(): array
    {
        return $_COOKIE;
    }
}