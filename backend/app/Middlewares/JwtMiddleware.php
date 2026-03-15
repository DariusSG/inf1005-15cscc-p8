<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{   
     /**
     * Handle JWT authentication and optional role-based access
     *
     * @param array $roles Allowed roles for this route (optional)
     */
    public static function handle(array $roles = [])
    {
        $header = Request::header('Authorization');
        if (!$header) {
            Response::json(["error" => "Missing token"], 401);
        }

        $token = str_replace("Bearer ", "", $header);

        try {
            // Validate access token and get payload
            $tokenService = Container::resolve('TokenService');
            $payload = $tokenService->verifyAccessToken($token);

            // RBAC check
            if (!empty($roles) && !in_array($payload->role, $roles)) {
                Response::json(["error" => "Forbidden"], 403);
            }

            Request::setContext('user_id', $payload->sub);
            Request::setContext('user_role', $payload->role);
            Request::setContext('token', $payload);
        } catch (ExpiredException $e) {

            $refresh = Request::header('X-Refresh-Token');

            if (!$refresh) {
                Response::json(["error"=>"Access token expired"],401);
            }

            $tokenService = Container::resolve('TokenService');
            $authService = Container::resolve('AuthService');
            $tokens = $authService->refresh($refresh);

            header("X-New-Access-Token: ".$tokens['access_token']);

            $payload = $tokenService->verifyAccessToken($tokens['access_token']);

            Request::setContext('user_id', $payload->sub);
            Request::setContext('user_role', $payload->role);
            Request::setContext('token', $payload);
        } catch (\Exception $e) {
            Response::json(["error" => $e->getMessage()], 401);
        }
        
    }

    /**
     * Retrieve JWT payload for controllers
     */
    public static function getPayload()
    {
        return Request::context('token');
    }

    /**
     * Get authenticated user ID
     */
    public static function userId()
    {
        return Request::context('user_id');
    }

    /**
     * Get authenticated user role
     */
    public static function userRole()
    {
        return Request::context('user_role');
    }
}