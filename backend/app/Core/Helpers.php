<?php

namespace App\Core;

class Helpers
{
    private static ?array $cfg = null;

    private static function configPath(): string
    {
        return __DIR__ . '/../../config/config.php';
    }

    /**
     * Get config by dot-notation key, e.g. "database.host" or "app.installed".
     */
    public static function config(string $key, $default = null): mixed
    {
        if (self::$cfg === null) {
            self::$cfg = require self::configPath();
        }

        $parts = explode('.', $key);
        $value = self::$cfg;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Persist a top-level config key back to config/config.php and refresh
     * the in-memory cache.  Only scalar/bool values are supported.
     *
     * Used by AppServiceProvider to write 'app.installed = true' after the
     * first successful migration run (Nextcloud-style installed flag).
     */
    public static function setConfig(string $topKey, mixed $value): void
    {
        if (self::$cfg === null) {
            self::$cfg = require self::configPath();
        }

        self::$cfg[$topKey] = array_merge(
            self::$cfg[$topKey] ?? [],
            is_array($value) ? $value : []
        );

        if (!is_array($value)) {
            self::$cfg[$topKey] = $value;
        }
    }

    /**
     * Write a single key inside a top-level section, e.g. ('app', 'installed', true).
     * Rewrites config/config.php so the flag survives the next request.
     */
    public static function writeConfig(string $section, string $key, mixed $value): void
    {
        if (self::$cfg === null) {
            self::$cfg = require self::configPath();
        }

        // Update in-memory cache
        self::$cfg[$section][$key] = $value;

        // Rewrite the file
        $export = var_export(self::$cfg, true);
        $content = "<?php\n\nreturn " . $export . ";\n";
        file_put_contents(self::configPath(), $content, LOCK_EX);
    }
}

