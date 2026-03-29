<?php

require __DIR__.'/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Helpers;
use App\Providers\AppServiceProvider;
use App\Core\ErrorHandler;
use App\Middleware\CorsMiddleware;

ErrorHandler::register();

AppServiceProvider::register();

if (!Helpers::config('app.installed', false)) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => 'not_installed',
        'message' => 'The application has not been installed yet. '
                   . 'Run migrations via: ./occ app:migrate',
    ]);
    exit;
}

CorsMiddleware::handle();

$router = new Router();

$router->prefix('/api/v1', function ($router) {

    // --- Public Auth ---
    $router->post('/auth/login',             'AuthController@login');
    $router->post('/auth/refresh',           'AuthController@refresh');
    $router->post('/auth/register/request',  'AuthController@requestRegistration');
    $router->get('/auth/register/verify',    'AuthController@checkVerifyToken');
    $router->post('/auth/register/complete', 'AuthController@completeRegistration');

    // --- Admin Only Section ---
    $router->middleware(['JwtMiddleware:admin'], function ($router) {
        $router->get('/admin/users',            'AdminController@user_index');
        $router->get('/admin/users/{id}',       'AdminController@user_show');
        $router->get('/admin/reported-reviews', 'AdminController@reportedReviews_show');
        $router->post('/admin/modules',               'ModuleController@store');
    });

    // --- General Authenticated Section ---
    $router->middleware(['JwtMiddleware'], function ($router) {
        $router->get('/auth/me',      'AuthController@me');
        $router->post('/auth/logout', 'AuthController@logout');

        // Modules
        $router->get('/modules',        'ModuleController@index');
        $router->get('/modules/{code}', 'ModuleController@show');

        // Reviews
        $router->get('/reviews',                'ReviewController@index');
        $router->post('/reviews',               'ReviewController@store');
        $router->post('/reviews/{id}',          'ReviewController@update');
        $router->post('/reviews/{id}/vote',     'ReviewController@vote');
        $router->post('/reviews/{id}/report',   'ReviewController@report');
        $router->post('/reviews/{id}/comments', 'ReviewController@addComment');

        // Tutors & Study Groups
        $router->get('/tutors',        'TutorController@index');
        $router->post('/tutors',       'TutorController@store');
        $router->get('/study-groups',  'StudyGroupController@index');
        $router->post('/study-groups', 'StudyGroupController@store');

        // Help requests
        $router->get('/help-requests',               'HelpRequestController@index');
        $router->post('/help-requests',              'HelpRequestController@store');
        $router->get('/help-requests/{id}',          'HelpRequestController@show');
        $router->post('/help-requests/{id}/respond', 'HelpRequestController@respond');
        $router->post('/help-requests/{id}/solve',   'HelpRequestController@solve');
    });
});

$router->resolve();
