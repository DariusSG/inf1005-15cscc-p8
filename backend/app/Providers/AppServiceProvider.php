<?php

namespace App\Providers;

use App\Core\Container;
use App\Config\Database;
use App\Services\AuthService;
use App\Services\TokenService;
use App\Services\UserService;

class AppServiceProvider
{
    public static function register()
    {
        // Initialize Database
        Database::init();

        // Bind services to container as singletons
        Container::bind('AuthService', fn() => new AuthService(), true);
        Container::bind('TokenService', fn() => new TokenService(), true);
        Container::bind('UserService', fn() => new UserService(), true);
    }
}