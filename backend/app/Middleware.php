<?php

namespace App;

use App\Core\Cookie;
use App\Core\Request;
use App\Core\Helpers;
use App\Core\Response;
use App\Core\Container;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{   
     /**
     * Handle JWT authentication and optional role-based access
     *
     * @param string ...$roles Allowed roles for this route (variadic)
     */
    public static function handle(string ...$roles)
    {
        $header = Request::header('Authorization');
        if (!$header) {
            Response::json(["error" => "Missing token"], 401);
        }

        $token = str_replace("Bearer ", "", $header);

        try {
            // Validate access token and get payload
            $tokenService = Container::resolve('TokenService');
            $payload = $tokenService->verifyAccessToken($token);

            // RBAC check
            if (!empty($roles) && !in_array($payload->role, $roles, true)) {
                Response::json(["error" => "Forbidden"], 403);
            }

            Request::setContext('user_id', $payload->sub);
            Request::setContext('user_role', $payload->role);
            Request::setContext('token', $payload);
        } catch (ExpiredException $e) {
            Response::json(["error" => "Access token expired. Please refresh."], 401);
        } catch (\Exception $e) {
            Response::json(["error" => $e->getMessage()], 401);
        }
        
    }

    /**
     * Retrieve JWT payload for controllers
     */
    public static function getPayload()
    {
        return Request::context('token');
    }

    /**
     * Get authenticated user ID
     */
    public static function userId()
    {
        return Request::context('user_id');
    }

    /**
     * Get authenticated user role
     */
    public static function userRole()
    {
        return Request::context('user_role');
    }
}
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

class RateLimitMiddleware
{
    private const DEFAULT_LIMIT  = 60;
    private const DEFAULT_WINDOW = 60;   // seconds

    private const LOGIN_LIMIT  = 5;
    private const LOGIN_WINDOW = 300;    // 5 minutes

    private const STORAGE_DIR = __DIR__ . '/../../../storage/rate_limits';

    // ── Public entry point ────────────────────────────────────────────────

    public static function handle(): void
    {
        [$type, $limit, $window] = self::resolvePolicy();

        [$allowed, $remaining, $reset] = self::check($type, $limit, $window);

        header("X-RateLimit-Limit: {$limit}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$reset}");

        if (!$allowed) {
            Response::json([
                'error'   => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }
    }

    // ── Policy resolution ─────────────────────────────────────────────────

    private static function resolvePolicy(): array
    {
        $uri = Request::uri();

        if (
            str_starts_with($uri, '/api/v1/auth/login') ||
            str_starts_with($uri, '/api/v1/auth/refresh') ||
            str_starts_with($uri, '/api/v1/auth/register')
        ) {
            return ['login', self::LOGIN_LIMIT, self::LOGIN_WINDOW];
        }

        return ['default', self::DEFAULT_LIMIT, self::DEFAULT_WINDOW];
    }

    // ── Core check (file-based, flock-protected) ──────────────────────────

    /**
     * @return array{bool, int, int}  [allowed, remaining, reset_timestamp]
     */
    private static function check(string $type, int $limit, int $window): array
    {
        self::ensureStorageDir();

        $ip   = preg_replace('/[^a-fA-F0-9:.]/', '_', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $file = self::STORAGE_DIR . "/{$type}_{$ip}.json";

        $fp = fopen($file, 'c+');
        if (!$fp) {
            // Cannot open file — fail open (allow request) to avoid blocking all traffic
            return [true, $limit - 1, time() + $window];
        }

        flock($fp, LOCK_EX);

        $now  = time();
        $data = [];

        $raw = stream_get_contents($fp);
        if ($raw) {
            $data = json_decode($raw, true) ?? [];
        }

        $windowStart = $data['window_start'] ?? $now;
        $count       = $data['count']        ?? 0;

        // Reset window if expired
        if ($now - $windowStart >= $window) {
            $windowStart = $now;
            $count       = 0;
        }

        $reset     = $windowStart + $window;
        $allowed   = $count < $limit;
        $remaining = max(0, $limit - $count - ($allowed ? 1 : 0));

        if ($allowed) {
            $count++;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode(['window_start' => $windowStart, 'count' => $count]));
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return [$allowed, $remaining, $reset];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private static function ensureStorageDir(): void
    {
        if (!is_dir(self::STORAGE_DIR)) {
            mkdir(self::STORAGE_DIR, 0750, true);
        }
    }
}