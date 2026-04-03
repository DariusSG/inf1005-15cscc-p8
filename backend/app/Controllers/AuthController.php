<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Cookie;
use App\Core\Logger;
use App\Config\JwtConfig;
use App\Middleware\JwtMiddleware;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\VerificationService;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;

#[OA\Tag(
    name: 'Authentication',
    description: 'User authentication and registration endpoints'
)]
#[OA\Schema(
    schema: "TokenResponse",
    properties: [
        new OA\Property(property: "access_token", type: "string"),
        new OA\Property(property: "refresh_token", type: "string", nullable: true),
        new OA\Property(property: "token_type", type: "string", example: "bearer"),
        new OA\Property(property: "expires_in", type: "integer", example: 3600)
    ]
)]
#[OA\Schema(
    schema: "LoginRequest",
    required: ["email", "password"],
    properties: [
        new OA\Property(property: "email", type: "string", format: "email"),
        new OA\Property(property: "password", type: "string", format: "password")
    ]
)]
class AuthController
{
    public function __construct(
        protected readonly AuthService $authService,
        protected readonly UserService $userService,
        protected readonly VerificationService $verificationService,
    ) {}

   #[OA\Post(
        path: "/auth/register/request",
        summary: "Request registration invite",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ["email"],
                properties: [new OA\Property(property: "email", type: "string", format: "email")]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(properties: [new OA\Property(property: "message", type: "string")])),
            new OA\Response(response: 400, description: "Invalid Arguments", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function requestRegistration(): void
    {
        $data  = Request::body();
        $email = trim($data['email'] ?? '');

        if (!$email) {
            Response::json(['message' => 'email is required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $this->authService->requestRegistration($email);

            Response::json(['message' => 'If that email is valid, a registration link has been sent.']);
        } catch (InvalidArgumentException $e) {
            Logger::channel()->error('Error at AuthController@requestRegistration', ['exception' => $e]);
            Response::json(['message' => 'Invalid registration data. Have you registered before?', 'code' => 'API_ERROR'], 400);
        } catch (RuntimeException|Exception $e) {
            Logger::channel()->error('Error at AuthController@requestRegistration', ['exception' => $e]);
            Response::json(['message' => 'An unexpected error occurred', 'code' => 'API_ERROR'], 500);
        }
    }

    #[OA\Get(
        path: "/auth/register/verify",
        summary: "Verify token before showing form",
        tags: ["Authentication"],
        parameters: [
            new OA\Parameter(name: "token", in: "query", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Valid token",
                content: new OA\JsonContent(properties: [new OA\Property(property: "email", type: "string")])
            ),
            new OA\Response(response: 400, description: "Invalid or missing token", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function checkVerifyToken(): void
    {
        $token = trim(Request::query('token', ''));

        if (!$token) {
            Response::json(['message' => 'token is required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $email = $this->verificationService->verifyToken($token);

            // Return masked email so the frontend can show "Registering as …@sit.…"
            // without exposing the full address in the JS bundle
            Response::json(['email' => $this->maskEmail($email)]);
        } catch (RuntimeException $e) {
            Logger::channel()->error('Error at AuthController@checkVerifyToken', ['exception' => $e]);
            Response::json(['message' => 'Invalid token', 'code' => 'API_ERROR'], 400);
        }
    }

    #[OA\Post(
        path: "/auth/register/complete",
        summary: "Complete registration using the invite token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["token", "name", "password"],
                properties: [
                    new OA\Property(property: "token", type: "string", description: "The verification token from the email"),
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "password", type: "string", format: "password", minLength: 8)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Account created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TokenResponse")
            ),
            new OA\Response(response: 400, description: "Validation or token error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 409, description: "User already exists or token expired", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function completeRegistration(): void
    {
        $data     = Request::body();
        $token    = trim($data['token']    ?? '');
        $name     = trim($data['name']     ?? '');
        $password = $data['password']      ?? '';

        if (!$token || !$name || !$password) {
            Response::json(['message' => 'token, name and password are all required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $tokens = $this->authService->completeRegistration($token, $name, $password);

            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());

            Response::json($tokens, 201);
        } catch (InvalidArgumentException $e) {
            Logger::channel()->error('Error at AuthController@completeRegistration', ['exception' => $e]);
            Response::json(['message' => 'Invalid registration data', 'code' => 'API_ERROR'], 400);
        } catch (RuntimeException $e) {
            Logger::channel()->error('Error at AuthController@completeRegistration', ['exception' => $e]);
            Response::json(['message' => 'Registration conflict', 'code' => 'API_ERROR'], 409);
        }
    }

    #[OA\Post(
        path: "/auth/password/forgot",
        summary: "Request a password reset email",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "If the account exists, a reset email is sent"),
            new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function forgotPassword(): void
    {
        $data  = Request::body();
        $email = trim($data['email'] ?? '');

        if (!$email) {
            Response::json(['message' => 'email is required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $this->authService->requestPasswordReset($email);
            Response::json([
                'message' => 'If that email exists, a password reset link has been sent.'
            ]);
        } catch (InvalidArgumentException $e) {
            Logger::channel()->error('Error at AuthController@forgotPassword', ['exception' => $e]);
            Response::json(['message' => 'Invalid request', 'code' => 'API_ERROR'], 400);
        } catch (Exception $e) {
            Logger::channel()->error('Error at AuthController@forgotPassword', ['exception' => $e]);
            Response::json(['message' => 'An unexpected error occurred', 'code' => 'API_ERROR'], 500);
        }
    }

    #[OA\Get(
        path: "/auth/password/verify",
        summary: "Verify a password reset token",
        tags: ["Authentication"],
        parameters: [
            new OA\Parameter(name: "token", in: "query", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Token is valid"),
            new OA\Response(response: 400, description: "Invalid token", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function verifyPasswordResetToken(): void
    {
        $token = trim(Request::query('token', ''));
        if (!$token) {
            Response::json(['message' => 'token is required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $email = $this->authService->verifyPasswordResetToken($token);
            Response::json([
                'email' => $this->maskEmail($email),
                'message' => 'Token is valid'
            ]);
        } catch (RuntimeException $e) {
            Logger::channel()->error('Error at AuthController@verifyPasswordResetToken', ['exception' => $e]);
            Response::json(['message' => 'Invalid token', 'code' => 'API_ERROR'], 400);
        }
    }

    #[OA\Post(
        path: "/auth/password/reset",
        summary: "Reset password using a reset token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["token", "password"],
                properties: [
                    new OA\Property(property: "token", type: "string"),
                    new OA\Property(property: "password", type: "string", format: "password", minLength: 8)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password reset successful"),
            new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function resetPassword(): void
    {
        $data = Request::body();
        $token = trim($data['token'] ?? '');
        $password = (string)($data['password'] ?? '');

        if (!$token || !$password) {
            Response::json(['message' => 'token and password are required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $this->authService->completePasswordReset($token, $password);
            Response::json(['message' => 'Password reset successful.']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Logger::channel()->error('Error at AuthController@resetPassword', ['exception' => $e]);
            Response::json(['message' => 'Invalid request', 'code' => 'API_ERROR'], 400);
        }
    }

    #[OA\Post(
        path: "/auth/login",
        summary: "Login",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: "#/components/schemas/LoginRequest")),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Success", 
                content: new OA\JsonContent(ref: "#/components/schemas/TokenResponse")
            ),
            new OA\Response(response: 401, description: "Invalid credentials", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function login(): void
    {
        $data     = Request::body();
        $email    = $data['email']    ?? '';
        $password = $data['password'] ?? '';

        try {
            $tokens = $this->authService->login($email, $password);

            if (!$tokens) {
                Response::json(['message' => 'Invalid credentials', 'code' => 'API_ERROR'], 401);
            }

            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());

            Response::json($tokens);
        } catch (\Exception $e) {
            Logger::channel()->error('Error at AuthController@login', ['exception' => $e]);
            Response::json(['message' => 'An unexpected error occurred', 'code' => 'API_ERROR'], 500);
        }
    }

    #[OA\Get(
        path: "/auth/me",
        summary: "Get current user",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Current user", 
                content: new OA\JsonContent(ref: "#/components/schemas/User")
            ),
            new OA\Response(response: 401, description: "Unauthorized", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "User not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function me()
    {
        $payload = JwtMiddleware::getPayload();
        if (!$payload) {
            Response::json(['message' => 'Unauthorized', 'code' => 'API_ERROR'], 401);
        }

        try {
            $user = $this->userService->getUserById($payload->sub);
            Response::json($user);
        } catch (\Exception $e) {
            Logger::channel()->error('Error at AuthController@me', ['exception' => $e]);
            Response::json(['message' => 'User not found', 'code' => 'API_ERROR'], 404);
        }
    }

    #[OA\Delete(
        path: "/auth/me",
        summary: "Delete current user",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Account deleted"),
            new OA\Response(response: 401, description: "Unauthorized", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "User not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function deleteMe(): void
    {
        $userId = JwtMiddleware::userId();
        if (!$userId) {
            Response::json(['message' => 'Unauthorized', 'code' => 'API_ERROR'], 401);
        }

        try {
            $this->userService->deleteUser((int) $userId);
            Cookie::delete('refresh_token');
            Response::json(['message' => 'Account deleted']);
        } catch (InvalidArgumentException $e) {
            Logger::channel()->error('Error at AuthController@deleteMe', ['exception' => $e]);
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 404);
        } catch (\Exception $e) {
            Logger::channel()->error('Error at AuthController@deleteMe', ['exception' => $e]);
            Response::json(['message' => 'An unexpected error occurred', 'code' => 'API_ERROR'], 500);
        }
    }

    #[OA\Post(
        path: "/auth/refresh",
        summary: "Refresh access token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "refresh_token", type: "string", description: "Optional if sent via cookie")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed",
                content: new OA\JsonContent(ref: "#/components/schemas/TokenResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Refresh token missing",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized / Invalid Refresh Token",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function refresh(): void
    {
        $data         = Request::body();
        $refreshToken = Cookie::get('refresh_token', $data['refresh_token'] ?? null);

        if (!$refreshToken) {
            Response::json(['message' => 'Refresh token required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $tokens = $this->authService->refresh($refreshToken);
            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());
            unset($tokens['refresh_token']);
            Response::json($tokens);
        } catch (\Exception $e) {
            Logger::channel()->error('Error at AuthController@refresh', ['exception' => $e]);
            Response::json(['message' => 'Invalid refresh token', 'code' => 'API_ERROR'], 401);
        }
    }

    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout user and invalidate tokens",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "refresh_token", type: "string", description: "Optional if sent via cookie"),
                    new OA\Property(property: "access_token", type: "string", description: "The current access token to invalidate")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Logged out successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Logged out")]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Refresh token missing",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Session already expired",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function logout(): void
    {
        $data         = Request::body();
        $refreshToken = Cookie::get('refresh_token', $data['refresh_token'] ?? null);
        $accessToken  = $data['access_token']  ?? null;

        if (!$refreshToken) {
            Response::json(['message' => 'Refresh token required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $result = $this->authService->logout($refreshToken, $accessToken);
            Cookie::delete('refresh_token');
            Response::json($result);
        } catch (\Exception $e) {
            Logger::channel()->error('Error at AuthController@logout', ['exception' => $e]);
            Response::json(['message' => 'Logout failed', 'code' => 'API_ERROR'], 401);
        }
    }

    #[OA\Post(
        path: "/auth/password/change",
        summary: "Change password for the authenticated user",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["current_password", "new_password"],
                properties: [
                    new OA\Property(property: "current_password", type: "string", format: "password"),
                    new OA\Property(property: "new_password", type: "string", format: "password", minLength: 8)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password changed successfully"),
            new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 401, description: "Unauthorized", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function changePassword(): void
    {
        $userId = JwtMiddleware::userId();
        if (!$userId) {
            Response::json(['message' => 'Unauthorized', 'code' => 'API_ERROR'], 401);
        }

        $data = Request::body();
        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');

        if (!$currentPassword || !$newPassword) {
            Response::json(['message' => 'current_password and new_password are required', 'code' => 'API_ERROR'], 400);
        }

        try {
            $this->authService->changePassword((int)$userId, $currentPassword, $newPassword);
            Cookie::delete('refresh_token');
            Response::json(['message' => 'Password changed successfully. Please sign in again.']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Logger::channel()->error('Error at AuthController@changePassword', ['exception' => $e]);
            Response::json(['message' => 'Invalid request', 'code' => 'API_ERROR'], 400);
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
