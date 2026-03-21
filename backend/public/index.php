<?php

require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Router;
use App\Providers\AppServiceProvider;
use App\Core\ErrorHandler;
use App\Middleware\CorsMiddleware;

/*
|--------------------------------------------------------------------------
| Load Environment
|--------------------------------------------------------------------------
*/
$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

/*
|--------------------------------------------------------------------------
| Register Error Handler
|--------------------------------------------------------------------------
*/
ErrorHandler::register();

/*
|--------------------------------------------------------------------------
| Initialize Services / Database
|--------------------------------------------------------------------------
*/
AppServiceProvider::register();

/*
|--------------------------------------------------------------------------
| Global CORS Middleware
|--------------------------------------------------------------------------
*/
CorsMiddleware::handle();

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

$router = new Router();

$router->middleware(['CorsMiddleware', 'RateLimitMiddleware'], function ($router) {

    $router->prefix('/api/v1', function ($router) {

        // ── Auth (public) ────────────────────────────────────────────────
        $router->post('/auth/login',    'AuthController@login');
        $router->post('/auth/refresh',  'AuthController@refresh');
        $router->post('/auth/register/request',  'AuthController@requestRegistration');
        $router->get('/auth/register/verify',    'AuthController@checkVerifyToken');
        $router->post('/auth/register/complete', 'AuthController@completeRegistration');

        // ── Authenticated ────────────────────────────────────────────────
        $router->middleware(['JwtMiddleware'], function ($router) {

            $router->get('/auth/me',  'AuthController@me');
            $router->post('/auth/logout', 'AuthController@logout');

            // Users (admin only)
            $router->middleware(['JwtMiddleware:admin'], function ($router) {
                $router->get('/users',      'UserController@index');
                $router->get('/users/{id}', 'UserController@show');
            });

            // Modules
            $router->get('/modules',       'ModuleController@index');
            $router->get('/modules/{code}', 'ModuleController@show');

            // Reviews
            $router->post('/reviews',               'ReviewController@store');
            $router->post('/reviews/{id}',          'ReviewController@update');  // PUT-as-POST fallback
            $router->post('/reviews/{id}/vote',     'ReviewController@vote');
            $router->post('/reviews/{id}/report',   'ReviewController@report');
            $router->post('/reviews/{id}/comments', 'ReviewController@addComment');

            // Tutors
            $router->get('/tutors',  'TutorController@index');
            $router->post('/tutors', 'TutorController@store');

            // Study groups
            $router->get('/study-groups',  'StudyGroupController@index');
            $router->post('/study-groups', 'StudyGroupController@store');

            // Help requests
            $router->get('/help-requests',  'HelpRequestController@index');
            $router->post('/help-requests', 'HelpRequestController@store');

            // Admin
            $router->middleware(['JwtMiddleware:admin'], function ($router) {
                $router->get('/admin/reported-reviews', 'AdminController@reportedReviews');
            });
        });
    });
});

$router->resolve();