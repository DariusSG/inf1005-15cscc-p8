<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Core\Validators;
use App\Core\Request;
use App\Core\Logger;
use App\Models\PasswordReset;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Carbon\Carbon;

class AuthService
{
    public function __construct(
        protected readonly TokenService $tokenService,
        protected readonly UserService $userService,
        protected readonly VerificationService $verificationService,
    ) {}

    private const int MIN_PASSWORD_LENGTH = 8;

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

    /**
     * @throws Exception
     */
    public function requestPasswordReset(string $email): void
    {
        $email = Validators::email($email);
        if (!$email) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        $user = UserRepository::findByEmail($email);
        if (!$user) {
            return;
        }

        PasswordReset::where('email', strtolower($email))
            ->where('used', false)
            ->update(['used' => true]);

        $rawToken = bin2hex(random_bytes(32));
        $storedHash = hash('sha256', $rawToken);

        PasswordReset::create([
            'email' => strtolower($email),
            'token' => $storedHash,
            'expires_at' => Carbon::now()->addSeconds((int) \App\Config\Mail::resetTtl()),
            'used' => false,
        ]);

        $this->verificationService->sendPasswordReset($email, $rawToken);
    }

    public function verifyPasswordResetToken(string $rawToken): string
    {
        $record = $this->findValidPasswordResetRecord($rawToken);
        return $record->email;
    }

    public function completePasswordReset(string $rawToken, string $newPassword): void
    {
        $record = $this->findValidPasswordResetRecord($rawToken);
        $this->assertPasswordValid($newPassword);

        $user = UserRepository::findByEmail($record->email);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $user->password = password_hash($newPassword, PASSWORD_ARGON2ID);
        $user->save();

        $record->used = true;
        $record->save();

        $this->tokenService->revokeAllTokensForUser((int) $user->id);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $user = UserRepository::findByIdWithPassword($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        if (!password_verify($currentPassword, $user->password)) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }

        $this->assertPasswordValid($newPassword);

        if (password_verify($newPassword, $user->password)) {
            throw new InvalidArgumentException('New password must be different from current password.');
        }

        $user->password = password_hash($newPassword, PASSWORD_ARGON2ID);
        $user->save();

        $this->tokenService->revokeAllTokensForUser((int) $user->id);
    }

    private function findValidPasswordResetRecord(string $rawToken): PasswordReset
    {
        $hash = hash('sha256', $rawToken);

        $record = PasswordReset::where('token', $hash)
            ->where('used', false)
            ->first();

        if (!$record) {
            throw new RuntimeException('Invalid or already-used reset link.');
        }

        if (Carbon::now()->gt($record->expires_at)) {
            throw new RuntimeException('This reset link has expired. Please request a new one.');
        }

        return $record;
    }

    private function assertPasswordValid(string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }
    }
}