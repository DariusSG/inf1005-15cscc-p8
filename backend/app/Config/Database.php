<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Core\Helpers;

class Database
{
    public static function init()
    {   
        self::validateConfig();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => Helpers::config('database.driver', 'mysql'),
            'host'      => Helpers::config('database.host'),
            'database'  => Helpers::config('database.name'),
            'username'  => Helpers::config('database.username'),
            'password'  => Helpers::config('database.password'),
            'charset'   => Helpers::config('database.charset', 'utf8mb4'),
            'collation' => Helpers::config('database.collation', 'utf8mb4_unicode_ci'),
            'prefix'    => Helpers::config('database.prefix', ''),
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    private static function validateConfig(): void
    {
        $required = [
            'database.host' => 'DB_HOST',
            'database.name' => 'DB_NAME',
            'database.username' => 'DB_USER',
            'database.password' => 'DB_PASS',
        ];

        $missing = [];
        foreach ($required as $key => $envVar) {
            $value = Helpers::config($key);
            if (empty($value)) {
                $missing[] = $envVar;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing)
            );
        }
    }


    public static function connection()
    {
        return Capsule::connection();
    }
}