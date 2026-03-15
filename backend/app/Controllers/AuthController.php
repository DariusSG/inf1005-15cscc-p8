<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Repositories\UserRepository;
use App\Services\TokenService;
use App\Middleware\JwtMiddleware;

/**
 * @OA\Info(
 *   title="My API",
 *   version="1.0.0",
 *   description="API for JWT Authentication"
 * )
 *
 * @OA\Server(
 *   url="/api/v1",
 *   description="API v1"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */
class AuthController
{
    protected $userService;
    protected $authService;

    public function __construct()
    {
        $this->authService = Container::resolve('AuthService');
        $this->userService = Container::resolve('UserService');
    }

    // -----------------------------
    // Login: returns access + refresh tokens
    // -----------------------------
    /**
     * @OA\Post(
     *   path="/auth/login",
     *   summary="Login",
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Successful login"),
     *   @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login()
    {
        $data = Request::body();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $tokens = $this->authService->login($email, $password);

            if (!$tokens) {
                Response::json(["error"=>"Invalid credentials"], 401);
            }

            Response::json($tokens);
        } catch (\Exception $e) {
            Response::json(["error"=>$e->getMessage()], 500);
        }
    }

    // -----------------------------
    // Register: creates a new user
    // -----------------------------
    /**
     * @OA\Post(
     *   path="/auth/register",
     *   summary="Register",
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="User created"),
     *   @OA\Response(response=400, description="Bad request")
     * )
     */
    public function register()
    {
        $data = Request::body();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $this->userService->createUser($email, password_hash($password, PASSWORD_ARGON2ID));
            Response::json(["message"=>"User created"]);
        } catch (\Exception $e) {
            Response::json(["error"=>$e->getMessage()], 400);
        }
    }

    // -----------------------------
    // Get current authenticated user
    // -----------------------------
    /**
     * @OA\Get(
     *   path="/auth/me",
     *   summary="Get current authenticated user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Successful response"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me()
    {
        $payload = JwtMiddleware::getPayload();

        if (!$payload) {
            Response::json(["error"=>"Unauthorized"],401);
        }

        try {
            $userService = Container::resolve('UserService');
            $user = $userService->getUserById($payload->sub);
            Response::json($user);
        } catch (\Exception $e) {
            Response::json(["error"=>$e->getMessage()],404);
        }
    }

    // -----------------------------
    // Refresh access token using refresh token
    // -----------------------------
    /**
     * @OA\Post(
     *   path="/auth/refresh",
     *   summary="Refresh access token",
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="refresh_token", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Token refreshed"),
     *   @OA\Response(response=400, description="Bad request"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refresh()
    {
        $data = Request::body();
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            Response::json(["error" => "Refresh token required"], 400);
        }

        try {
            $tokens = $this->authService->refresh($refreshToken);
            Response::json($tokens);
        } catch (\Exception $e) {
            Response::json(["error"=>$e->getMessage()], 401);
        }
    }

    // -----------------------------
    // Logout: revoke the refresh token
    // -----------------------------
    /**
     * @OA\Post(
     *  path="/auth/logout",
     * summary="Logout",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *  @OA\MediaType(
     *    mediaType="application/json",
     *   @OA\Schema(
     *    @OA\Property(property="refresh_token", type="string")
     *  )
     * )
     * ),
     * @OA\Response(response=200, description="Logged out"),
     * @OA\Response(response=400, description="Bad request"),
     * @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout()
    {
        $data = Request::body();
        $refreshToken = $data['refresh_token'] ?? null;
        $accessToken  = $data['access_token'] ?? null;

        if (!$refreshToken) {
            Response::json(["error"=>"Refresh token required"], 400);
        }

        try {
            $result = $this->authService->logout($refreshToken, $accessToken);
            Response::json($result);
        } catch (\Exception $e) {
            Response::json(["error"=>$e->getMessage()], 401);
        }
    }
}