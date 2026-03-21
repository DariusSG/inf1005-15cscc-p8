<?php

namespace App\Providers;

use App\Core\Container;
use App\Config\Database;
use App\Services\AuthService;
use App\Services\TokenService;
use App\Services\UserService;
use App\Services\MailService;
use App\Services\VerificationService;

class AppServiceProvider
{
    public static function register()
    {
        Database::init();

        Container::bind('TokenService',       fn() => new TokenService(),                                       true);
        Container::bind('MailService',        fn() => new MailService(),                                        true);
        Container::bind('VerificationService',fn() => new VerificationService(Container::resolve('MailService')),true);
        Container::bind('AuthService',        fn() => new AuthService(),                                        true);
        Container::bind('UserService',        fn() => new UserService(),                                        true);
    }
}