<?php

namespace App\Providers;

use App\Core\Container;
use App\Core\Migrator;
use App\Core\Helpers;
use App\Config\Database;
use App\Services\AuthService;
use App\Services\TokenService;
use App\Services\UserService;
use App\Services\MailService;
use App\Services\VerificationService;

class AppServiceProvider
{
    public static function register(): void
    {
        Database::init();

        if (!Helpers::config('app.installed', false)) {
            Migrator::run();
        }

        Container::singleton(TokenService::class, TokenService::class);
        Container::singleton(MailService::class, MailService::class);
        Container::singleton(AuthService::class, AuthService::class);
        Container::singleton(UserService::class, UserService::class);
        Container::singleton(VerificationService::class, VerificationService::class);
    }
}