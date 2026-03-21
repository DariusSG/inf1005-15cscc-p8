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

    // ── Step 1: validate email + send invite ────────────────────────────

    /**
     * Validates the SIT email domain and fires off the invite email.
     * Does NOT create a user yet.
     */
    public function requestRegistration(string $email): void
    {
        /** @var VerificationService $vs */
        $vs = Container::resolve('VerificationService');
        $vs->sendInvite($email); // throws InvalidArgumentException on bad domain
    }

    // ── Step 2: complete registration via token ─────────────────────────

    /**
     * Verifies the invite token, creates the user account, returns tokens.
     */
    public function completeRegistration(string $rawToken, string $name, string $password): array
    {
        /** @var VerificationService $vs */
        $vs = Container::resolve('VerificationService');

        // Will throw if token is invalid/expired/used
        $email = $vs->verifyToken($rawToken);

        if (UserRepository::findByEmail($email)) {
            throw new \RuntimeException('An account for this email already exists.');
        }

        if (strlen(trim($name)) < 2) {
            throw new \InvalidArgumentException('Name must be at least 2 characters.');
        }

        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }

        $hashed = password_hash($password, PASSWORD_ARGON2ID);
        $user   = UserRepository::create($email, $hashed, 'student', trim($name));

        // Mark token as consumed only after account creation succeeds
        $vs->consumeToken($rawToken);

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'student');
    }

    // ── Login ────────────────────────────────────────────────────────────

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

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'student');
    }

    // ── Refresh ──────────────────────────────────────────────────────────

    public function refresh(string $refreshToken): array
    {
        $payload = $this->tokenService->verifyRefreshToken($refreshToken);
        $this->tokenService->revokeRefreshToken($payload->jti);

        $user = UserRepository::findById($payload->sub);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'student');
    }

    // ── Logout ───────────────────────────────────────────────────────────

    public function logout(string $refreshToken, ?string $accessToken = null): array
    {
        $refreshPayload = $this->tokenService->verifyRefreshToken($refreshToken);
        $this->tokenService->revokeRefreshToken($refreshPayload->jti);
        $this->tokenService->revokeAccessByRefreshJti($refreshPayload->jti);

        if ($accessToken) {
            try {
                $accessPayload = $this->tokenService->verifyAccessToken($accessToken);
                $this->tokenService->revokeAccessToken($accessPayload->jti);
            } catch (\Exception $e) {
                Log::channel()->warning('Access token logout failed: ' . $e->getMessage(), [
                    'user_id' => Request::context('user_id'),
                ]);
            }
        }

        return ['message' => 'Logged out successfully'];
    }
}