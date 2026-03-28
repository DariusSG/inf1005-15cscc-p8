<?php

namespace App\Core;

use Monolog\Logger as MonologInstance;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

class Logger
{
    private static ?MonologInstance $instance = null;
    private const string LOG_PATH = __DIR__ . '/../../storage/logs/app.log';

    public static function channel(): MonologInstance
    {
        if (self::$instance === null) {
            self::ensureDirectoryExists();

            $log = new MonologInstance('app');

            // Use Level::Debug (PHP 8.1+ Enums) for better type safety
            $handler = new StreamHandler(self::LOG_PATH, Level::Debug);

            $log->pushHandler($handler);
            self::$instance = $log;
        }

        return self::$instance;
    }

    /**
     * Ensures the log directory exists and the file has correct permissions.
     */
    private static function ensureDirectoryExists(): void
    {
        $dir = dirname(self::LOG_PATH);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists(self::LOG_PATH)) {
            touch(self::LOG_PATH);
            chmod(self::LOG_PATH, 0640);
        }
    }
}