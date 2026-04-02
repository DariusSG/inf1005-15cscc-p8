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
    $router->post('/auth/password/forgot',   'AuthController@forgotPassword');
    $router->get('/auth/password/verify',    'AuthController@verifyPasswordResetToken');
    $router->post('/auth/password/reset',    'AuthController@resetPassword');

    // --- Public Browse (no auth required) ---
    $router->get('/stats',                       'StatsController@index');
    $router->get('/modules',                     'ModuleController@index');
    $router->get('/modules/{code}',              'ModuleController@show');
    $router->get('/tutors',                      'TutorController@index');
    $router->get('/study-groups',                'StudyGroupController@index');
    $router->get('/help-requests',               'HelpRequestController@index');
    $router->get('/help-requests/{id}',          'HelpRequestController@show');

    // --- Admin Only Section ---
    $router->middleware(['JwtMiddleware:admin'], function ($router) {
        $router->get('/admin/users',             'AdminController@user_index');
        $router->get('/admin/users/{id}',        'AdminController@user_show');
        $router->delete('/admin/users/{id}',     'AdminController@user_delete');
        $router->get('/admin/reviews/report',    'AdminController@reportedReviews_show');
        $router->delete('/admin/reviews/report', 'AdminController@reportedReviews_delete');
        $router->post('/admin/modules',          'AdminController@module_store');
        $router->put('/admin/modules/{code}',    'AdminController@module_update');
        $router->delete('/admin/modules/{code}', 'AdminController@module_destroy');
    });

    // --- General Authenticated Section ---
    $router->middleware(['JwtMiddleware'], function ($router) {
        $router->get('/auth/me',      'AuthController@me');
        $router->delete('/auth/me',    'AuthController@deleteMe');
        $router->post('/auth/logout', 'AuthController@logout');
        $router->post('/auth/password/change', 'AuthController@changePassword');

        // Reviews
        $router->get('/reviews',                'ReviewController@index');
        $router->post('/reviews',               'ReviewController@store');
        $router->post('/reviews/{id}',          'ReviewController@update');
        $router->post('/reviews/{id}/vote',     'ReviewController@vote');
        $router->post('/reviews/{id}/report',   'ReviewController@report');
        $router->post('/reviews/{id}/comments', 'ReviewController@addComment');

        // Tutors & Study Groups (write actions only)
        $router->post('/tutors',                  'TutorController@store');
        $router->put('/tutors/{id}',              'TutorController@update');
        $router->post('/study-groups',             'StudyGroupController@store');
        $router->put('/study-groups/{id}',         'StudyGroupController@update');
        $router->post('/study-groups/{id}/join',   'StudyGroupController@join');
        $router->post('/study-groups/{id}/leave',  'StudyGroupController@leave');

        // Help requests (write actions only)
        $router->post('/help-requests',              'HelpRequestController@store');
        $router->put('/help-requests/{id}',          'HelpRequestController@update');
        $router->post('/help-requests/{id}/respond', 'HelpRequestController@respond');
        $router->post('/help-requests/{id}/solve',   'HelpRequestController@solve');
    });
});

$router->resolve();
