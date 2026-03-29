<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Cookie;
use App\Config\JwtConfig;
use App\Middleware\JwtMiddleware;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\VerificationService;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;

use App\Core\OpenApiSpec; // Ensure the OpenApiSpec class is included


#[OA\Tag(
    name: 'Authentication',
    description: 'User authentication and registration endpoints'
)]
#[OA\Schema(
    schema: "TokenResponse",
    properties: [
        new OA\Property(property: "access_token", type: "string"),
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
            Response::json(['error' => 'email is required'], 400);
        }

        try {
            $this->authService->requestRegistration($email);

            Response::json(['message' => 'If that email is valid, a registration link has been sent.']);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (RuntimeException|Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
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
        $token = trim($_GET['token'] ?? '');

        if (!$token) {
            Response::json(['error' => 'token is required'], 400);
        }

        try {
            $email = $this->verificationService->verifyToken($token);

            // Return masked email so the frontend can show "Registering as …@sit.…"
            // without exposing the full address in the JS bundle
            Response::json(['email' => $this->maskEmail($email)]);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 400);
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
            Response::json(['error' => 'token, name and password are all required'], 400);
        }

        try {
            $tokens = $this->authService->completeRegistration($token, $name, $password);

            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());

            Response::json($tokens, 201);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => 'Invalid registration data'], 400);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 409);
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
                Response::json(['error' => 'Invalid credentials'], 401);
            }

            Cookie::setRefreshToken($tokens['refresh_token'], JwtConfig::refresh_ttl());

            Response::json($tokens);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
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
            Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $user = $this->userService->getUserById($payload->sub);
            Response::json($user);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 404);
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
            Response::json(['error' => 'Refresh token required'], 400);
        }

        try {
            $result = $this->authService->logout($refreshToken, $accessToken);
            Cookie::delete('refresh_token');
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
