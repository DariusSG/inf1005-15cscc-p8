<?php

namespace App\Core;


class Helpers
{
    private static ?array $cfg = null;

    private static function configPath(): string
    {
        return __DIR__ . '/../../config/config.php';
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        $config = self::loadConfig();
        $parts = explode('.', $key);

        foreach ($parts as $part) {
            if (!is_array($config) || !isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    public static function writeConfig(string $key, mixed $value): void
    {
        $config = self::loadConfig();
        $parts = explode('.', $key);
        $temp = &$config;

        // Navigate/build the nested array structure
        foreach ($parts as $part) {
            if (!isset($temp[$part]) || !is_array($temp[$part])) {
                $temp[$part] = [];
            }
            $temp = &$temp[$part];
        }

        $temp = $value;
        self::$cfg = $config;

        // Persist to disk
        self::persist($config);
    }

    private static function loadConfig(): array
    {
        if (self::$cfg === null) {
            $path = self::configPath();
            self::$cfg = file_exists($path) ? require $path : [];
        }
        return self::$cfg;
    }

    private static function persist(array $data): void
    {
        $path = self::configPath();

        // Use short array syntax [] instead of array() in the export
        $export = var_export($data, true)
                |> (fn($x) => preg_replace("/array \(|array\(/", "[", $x))
                |> (fn($x) => preg_replace("/\)/", "]", $x));

        $content = "<?php\n\n/**\n * Auto-generated Config File\n */\nreturn " . $export . ";\n";

        // Write with exclusive lock to prevent corruption during concurrent requests
        file_put_contents($path, $content, LOCK_EX);

        // If using OpCache, invalidate the file so PHP picks up changes immediately
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }
    }
}

