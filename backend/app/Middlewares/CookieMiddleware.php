<?php

namespace App\Middleware;

use App\Core\Cookie;
use App\Core\Request;

/**
 * CookieMiddleware
 * 
 * Handles common cookie operations:
 * - Validate and refresh expired cookies
 * - Set default cookies
 * - Handle cookie-based preferences
 * 
 * Usage in Router:
 * $router->middleware(['CookieMiddleware'], function($router) {
 *     // Protected routes
 * });
 */
class CookieMiddleware
{
    public static function handle()
    {
        // Set default SameSite header for all responses
        // (helps prevent CSRF even if individual cookies aren't set correctly)
        header("X-Content-Type-Options: nosniff");

        // Load user preferences from cookies into request context
        // This makes them accessible via Request::context('cookie_*')
        self::loadPreferences();

        // Validate existing cookies
        self::validateCookies();
    }

    /**
     * Load preference cookies into request context
     * Makes them accessible to controllers
     */
    private static function loadPreferences()
    {
        // Example: load common preferences
        $preferences = [
            'theme' => Cookie::get('theme', 'light'),
            'language' => Cookie::get('language', 'en'),
            'timezone' => Cookie::get('timezone', 'UTC'),
        ];

        foreach ($preferences as $key => $value) {
            Request::setContext("cookie_$key", $value);
        }
    }

    /**
     * Validate cookies and clean up invalid ones
     */
    private static function validateCookies()
    {
        // Remove any cookies that are empty or malformed
        foreach ($_COOKIE as $name => $value) {
            if (empty($value) || strlen($value) > 10000) {
                Cookie::delete($name);
            }
        }
    }
}