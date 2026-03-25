<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * File-based rate limiter — works with the Capsule-only setup (no Laravel
 * Cache facade required).  Each IP+type combination gets a small JSON file
 * in storage/rate_limits/.  A file lock (flock) makes the read-increment-write
 * cycle atomic enough for a single-server deployment.
 */
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
