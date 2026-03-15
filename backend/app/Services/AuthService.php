<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Core\Container;
use App\Core\Log;
use App\Core\Request;

class AuthService
{
    protected $tokenService;

    public function __construct()
    {
        $this->tokenService = Container::resolve('TokenService');
    }

    /**
     * Login: validate credentials, issue tokens
     */
    public function login(string $email, string $password): ?array
    {
        $user = UserRepository::findByEmail($email);
        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }

    if (password_needs_rehash($user->password, PASSWORD_ARGON2ID)) {
        $user->password = password_hash($password, PASSWORD_ARGON2ID);
        $user->save();
    }


        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'user');
    }

    /**
     * Refresh: rotate refresh token
     */
    public function refresh(string $refreshToken): array
    {
        $payload = $this->tokenService->verifyRefreshToken($refreshToken);

        // Revoke old refresh token
        $this->tokenService->revokeRefreshToken($payload->jti);

        $user = UserRepository::findById($payload->sub);
        if (!$user) {
            throw new \Exception("User not found");
        }

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'user');
    }

    /**
     * Logout: revoke refresh + access token
     */
    public function logout(string $refreshToken, ?string $accessToken = null): array
    {
        $refreshPayload = $this->tokenService->verifyRefreshToken($refreshToken);
        $this->tokenService->revokeRefreshToken($refreshPayload->jti);
        
        // revoke access tokens linked to refresh token
        $this->tokenService->revokeAccessByRefreshJti($refreshPayload->jti);

        if ($accessToken) {
            try {
                $accessPayload = $this->tokenService->verifyAccessToken($accessToken);
                $this->tokenService->revokeAccessToken($accessPayload->jti);
            } catch (\Exception $e) {
                Log::channel()->warning("Access token logout failed: ".$e->getMessage(), [
                    'token' => $accessToken,
                    'user_id' => Request::context('user_id')
                ]);
            }
        }

        return ["message" => "Logged out successfully"];
    }
}