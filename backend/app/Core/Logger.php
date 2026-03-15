<?php

namespace App\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Log
{   

    private static $instance;

    public static function channel()
    {
        if (!self::$instance) {
            $log = new Logger('app');
            $log->pushHandler(
                new StreamHandler(__DIR__.'/../../storage/logs/app.log')
            );

            // ensure file created with safe permissions
            if (!file_exists(__DIR__.'/../../storage/logs/app.log')) {
                touch(__DIR__.'/../../storage/logs/app.log');
                chmod(__DIR__.'/../../storage/logs/app.log', 0640);
            }
            self::$instance = $log;
        }

        return self::$instance;
    }
}