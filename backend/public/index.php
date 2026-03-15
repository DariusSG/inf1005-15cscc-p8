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

$router->prefix('/api/v1', function($router) {

    $router->prefix('/auth', function($router) {

        $router->post('/register', 'AuthController@register');
        $router->post('/login', 'AuthController@login');
        $router->post('/refresh', 'AuthController@refresh');
        $router->get('/me', 'AuthController@me', ['JwtMiddleware']);

    });

    $router->prefix('/users', function($router) {
        $router->get('/', 'UserController@index', ['JwtMiddleware','RateLimitMiddleware']);
        $router->get('/{id}', 'UserController@show', ['JwtMiddleware']);
    });

});

$router->resolve();