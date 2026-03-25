<?php

namespace App\Providers;

use App\Core\Container;
use App\Core\Migrator;
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

        // Always run pending migrations on every HTTP boot.
        // Safe: the migrations tracking table prevents re-running applied files.
        // On first boot, also seeds the admin user and writes installed=true
        // into config/config.php (Nextcloud-style installed flag).
        Migrator::run();

        Container::bind('TokenService',        fn() => new TokenService(),                                        true);
        Container::bind('MailService',         fn() => new MailService(),                                         true);
        Container::bind('VerificationService', fn() => new VerificationService(Container::resolve('MailService')), true);
        Container::bind('AuthService',         fn() => new AuthService(),                                         true);
        Container::bind('UserService',         fn() => new UserService(),                                         true);
    }
}
