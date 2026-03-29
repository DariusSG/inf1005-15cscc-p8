<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Core\Container;
use App\Core\Logger;
use App\Core\Request;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AuthService
{
    public function __construct(
        protected readonly TokenService $tokenService,
        protected readonly UserService $userService,
        protected readonly VerificationService $verificationService,
    ) {}

    /**
     * @throws Exception
     */
    public function requestRegistration(string $email): void
    {
        $this->verificationService->assertSitEmail($email);
        if (UserRepository::findByEmail($email)) {
            // We throw an exception so the Controller can return a 409 Conflict
            throw new InvalidArgumentException('An account with this email already exists.');
        }
        $this->verificationService->sendInvite($email);
    }

    /**
     * @throws Exception
     */
    public function completeRegistration(string $rawToken, string $name, string $password): array
    {
        $email = $this->verificationService->verifyToken($rawToken);

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        $hashed = password_hash($password, PASSWORD_ARGON2ID);

        $user = $this->userService->createUser($name, $email, $hashed);

        $this->verificationService->consumeToken($rawToken);

        AuditService::log('auth.registration.complete', [
            'user_id' => $user->id,
            'email' => $email
        ]);

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'student');
    }


    /**
     * @throws Exception
     */
    public function login(string $email, string $password): ?array
    {
        $user = UserRepository::findByEmail($email);
        $success = $user && password_verify($password, $user->password);

        AuditService::logLogin($email, $success,
            $success ? null : ($user ? 'invalid_password' : 'user_not_found'));

        if (!$success) {
            return null;
        }

        if (password_needs_rehash($user->password, PASSWORD_ARGON2ID)) {
            $user->password = password_hash($password, PASSWORD_ARGON2ID);
            $user->save();
        }

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'student');
    }


    /**
     * @throws Throwable
     */
    public function refresh(string $refreshToken): array
    {
        $payload = $this->tokenService->verifyAndConsumeRefreshToken($refreshToken);

        $user = UserRepository::findById($payload->sub);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        return $this->tokenService->rotateRefreshToken($user->id, $user->role ?? 'student');
    }


    /**
     * @throws Exception
     */
    public function logout(string $refreshToken, ?string $accessToken = null): array
    {
        $refreshPayload = $this->tokenService->verifyRefreshToken($refreshToken);
        $this->tokenService->revokeRefreshToken($refreshPayload->jti);
        $this->tokenService->revokeAccessByRefreshJti($refreshPayload->jti);

        if ($accessToken) {
            try {
                $accessPayload = $this->tokenService->verifyAccessToken($accessToken);
                $this->tokenService->revokeAccessToken($accessPayload->jti);
            } catch (Exception $e) {
                Logger::channel()->warning('Access token logout failed: ' . $e->getMessage(), [
                    'user_id' => Request::context('user_id'),
                ]);
            }
        }

        return ['message' => 'Logged out successfully'];
    }
}