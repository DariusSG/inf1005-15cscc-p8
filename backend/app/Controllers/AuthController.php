<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Core\Cookie;
use App\Config\JwtConfig;
use App\Middleware\JwtMiddleware;

class AuthController
{
    protected $authService;
    protected $userService;

    public function __construct()
    {
        $this->authService = Container::resolve('AuthService');
        $this->userService = Container::resolve('UserService');
    }

    // ── Step 1: request invite email ─────────────────────────────────────
    /**
     * @OA\Post(path="/auth/register/request",
     *   summary="Request registration invite for a SIT email",
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"email"},
     *       @OA\Property(property="email", type="string",
     *         example="john.doe.2023@sit.singaporetech.edu.sg")
     *     )
     *   )),
     *   @OA\Response(response=200, description="Invite sent"),
     *   @OA\Response(response=400, description="Invalid or non-SIT email"),
     *   @OA\Response(response=429, description="Rate limited")
     * )
     */
    public function requestRegistration()
    {
        $data  = Request::body();
        $email = trim($data['email'] ?? '');

        if (!$email) {
            Response::json(['error' => 'email is required'], 400);
        }

        try {
            $this->authService->requestRegistration($email);
            // Always return the same message to avoid email enumeration
            Response::json(['message' => 'If that email is valid, a registration link has been sent.']);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Step 1b: verify token is still valid (pre-flight for the form) ───
    /**
     * @OA\Get(path="/auth/register/verify",
     *   summary="Check invite token validity before showing the form",
     *   @OA\Parameter(name="token", in="query", required=true,
     *     @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Token valid — returns masked email"),
     *   @OA\Response(response=400, description="Invalid or expired token")
     * )
     */
    public function checkVerifyToken()
    {
        $token = trim($_GET['token'] ?? '');

        if (!$token) {
            Response::json(['error' => 'token is required'], 400);
        }

        try {
            $vs    = Container::resolve('VerificationService');
            $email = $vs->verifyToken($token);

            // Return masked email so the frontend can show "Registering as …@sit.…"
            // without exposing the full address in the JS bundle
            Response::json(['email' => $this->maskEmail($email)]);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    // ── Step 2: complete registration ────────────────────────────────────
    /**
     * @OA\Post(path="/auth/register/complete",
     *   summary="Complete registration using the invite token",
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"token","name","password"},
     *       @OA\Property(property="token",    type="string"),
     *       @OA\Property(property="name",     type="string"),
     *       @OA\Property(property="password", type="string")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Account created, returns access token"),
     *   @OA\Response(response=400, description="Validation or token error")
     * )
     */
    public function completeRegistration()
    {
        $data     = Request::body();
        $token    = trim($data['token']    ?? '');
        $name     = trim($data['name']     ?? '');
        $password = $data['password']      ?? '';

        if (!$token || !$name || !$password) {
            Response::json(['error' => 'token, name and password are all required'], 400);
        }

        try {
            $tokens = $this->authService->completeRegistration($token, $name, $password);

            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());
            unset($tokens['refresh_token']);

            Response::json($tokens, 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 409);
        }
    }

    // ── Login ─────────────────────────────────────────────────────────────
    /**
     * @OA\Post(path="/auth/login", summary="Login",
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"email","password"},
     *       @OA\Property(property="email",    type="string"),
     *       @OA\Property(property="password", type="string")
     *     )
     *   )),
     *   @OA\Response(response=200, description="Successful login"),
     *   @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login()
    {
        $data     = Request::body();
        $email    = $data['email']    ?? '';
        $password = $data['password'] ?? '';

        try {
            $tokens = $this->authService->login($email, $password);

            if (!$tokens) {
                Response::json(['error' => 'Invalid credentials'], 401);
            }

            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());
            unset($tokens['refresh_token']);

            Response::json($tokens);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Me ────────────────────────────────────────────────────────────────
    /**
     * @OA\Get(path="/auth/me", summary="Get current user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Current user"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me()
    {
        $payload = JwtMiddleware::getPayload();
        if (!$payload) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $user = $this->userService->getUserById($payload->sub);
            Response::json($user);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 404);
        }
    }

    // ── Refresh ───────────────────────────────────────────────────────────
    /**
     * @OA\Post(path="/auth/refresh", summary="Refresh access token",
     *   @OA\Response(response=200, description="Token refreshed"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refresh()
    {
        $data         = Request::body();
        $refreshToken = $data['refresh_token'] ?? Cookie::getRefreshToken();

        if (!$refreshToken) {
            Response::json(['error' => 'Refresh token required'], 400);
        }

        try {
            $tokens = $this->authService->refresh($refreshToken);
            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());
            unset($tokens['refresh_token']);
            Response::json($tokens);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    // ── Logout ────────────────────────────────────────────────────────────
    /**
     * @OA\Post(path="/auth/logout", summary="Logout",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout()
    {
        $data         = Request::body();
        $refreshToken = $data['refresh_token'] ?? Cookie::getRefreshToken();
        $accessToken  = $data['access_token']  ?? null;

        if (!$refreshToken) {
            Response::json(['error' => 'Refresh token required'], 400);
        }

        try {
            $result = $this->authService->logout($refreshToken, $accessToken);
            Cookie::deleteRefreshToken();
            Response::json($result);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 3);
        return $visible . str_repeat('*', max(0, strlen($local) - 3)) . '@' . $domain;
    }
}