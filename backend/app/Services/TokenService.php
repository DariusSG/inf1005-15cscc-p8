<?php

namespace App\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\JwtConfig;
use App\Models\Session;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\RefreshToken;
use Carbon\Carbon;
use Random\RandomException;
use Throwable;

class TokenService
{
    private const string ISSUER = "sitizen-api";
    private const string AUDIENCE = "sitizen-client";

    /**
     * Create access token, store JTI in sessions table
     * @throws Exception
     */
    public function createAccessToken(int $userId, string $role = "user", ?string $refreshJti = null): string
    {
        $jti = bin2hex(random_bytes(16));
        $payload = [
            "sub"  => $userId,
            "role" => $role,
            "type" => "access",
            "iat"  => time(),
            "exp"  => time() + JwtConfig::access_ttl(),
            "jti"  => $jti,
            "iss"  => self::ISSUER,
            "aud"  => self::AUDIENCE
        ];

        // Persist access token session
        Session::create([
            'user_id'    => $userId,
            'jti'        => $jti,
            'refresh_jti'=> $refreshJti,
            'expires_at' => Carbon::createFromTimestamp($payload['exp']),
            'revoked'    => false
        ]);

        $kid = JwtConfig::currentKid();
        return JWT::encode(
            $payload,
            JwtConfig::secret($kid),
            'HS256',
            $kid
        );
    }

    /**
     * Verify access token and check session (JTI)
     * @throws Exception
     */
    public function verifyAccessToken(string $token): object
    {
        $header = json_decode(base64_decode(explode('.', $token)[0]));
        
        $kid = $header->kid ?? JwtConfig::currentKid();

        $payload = JWT::decode(
            $token,
            new Key(JwtConfig::secret($kid), 'HS256')
        );

        if ($payload->iss !== self::ISSUER || $payload->aud !== self::AUDIENCE) {
            throw new Exception("Invalid token issuer/audience");
        }

        $session = Session::where('jti', $payload->jti)->first();
        if (!$session || $session->revoked || strtotime($session->expires_at) < time()) {
            throw new Exception("Access token revoked or expired");
        }

        return $payload;
    }


    /**
     * Revoke access token (by JTI)
     */
    public function revokeAccessToken(string $jti): void
    {
        $session = Session::where('jti', $jti)->first();
        if ($session) {
            $session->revoked = true;
            $session->save();
        }
    }

    /**
     * Create refresh token, store in DB for rotation
     * @throws Exception
     */
    public function createRefreshToken(int $userId): string
    {
        try {
            $jti = bin2hex(random_bytes(16));
        } catch (RandomException $e) {
            throw new Exception($e->getMessage());
        }

        $payload = [
            "sub"  => $userId,
            "type" => "refresh",
            "iat"  => time(),
            "exp"  => time() + JwtConfig::refresh_ttl(),
            "jti"  => $jti,
            "iss"  => self::ISSUER,
            "aud"  => self::AUDIENCE
        ];

        
        $kid = JwtConfig::currentKid();

        $tokenString = JWT::encode(
            $payload,
            JwtConfig::secret($kid),
            'HS256',
            $kid
        );
        
        $hash = hash('sha256', $tokenString);

        RefreshToken::create([
            'user_id'    => $userId,
            'jti'        => $jti,
            'token_hash' => $hash,
            'expires_at' => Carbon::createFromTimestamp($payload['exp']),
            'revoked'    => false
        ]);

        return $tokenString;
    }

    /**
     * Verify and atomically consume refresh token to prevent race conditions
     * @throws Throwable
     */
    public function verifyAndConsumeRefreshToken(string $token): object
    {
        $header = json_decode(base64_decode(explode('.', $token)[0]));
        $kid = $header->kid ?? JwtConfig::currentKid();

        $hash = hash('sha256', $token);

        // Use atomic transaction to prevent race conditions
        return Capsule::connection()->transaction(function () use ($token, $hash, $kid) {
            $payload = JWT::decode(
                $token,
                new Key(JwtConfig::secret($kid), 'HS256')
            );

            if ($payload->iss !== self::ISSUER || $payload->aud !== self::AUDIENCE) {
                throw new Exception("Invalid token issuer/audience");
            }

            $dbToken = RefreshToken::where('jti', $payload->jti)
                                   ->where('token_hash', $hash)
                                   ->lockForUpdate()  // Lock row for atomic update
                                   ->first();

            if (!$dbToken || $dbToken->revoked || Carbon::now()->gt($dbToken->expires_at)) {
                if ($dbToken && $dbToken->revoked) {
                    Session::where('refresh_jti', $payload->jti)->update(['revoked' => true]);
                }
                throw new Exception("Refresh token revoked or expired");
            }

            // Atomically mark as consumed
            $dbToken->revoked = true;
            $dbToken->save();

            return $payload;
        });
    }


    /**
     * Verify a refresh token without consuming it.
     * Used during logout to obtain the JTI before revoking.
     * @throws Exception
     */
    public function verifyRefreshToken(string $token): object
    {
        $header = json_decode(base64_decode(explode('.', $token)[0]));
        $kid    = $header->kid ?? JwtConfig::currentKid();

        $payload = JWT::decode(
            $token,
            new Key(JwtConfig::secret($kid), 'HS256')
        );

        if ($payload->iss !== self::ISSUER || $payload->aud !== self::AUDIENCE) {
            throw new Exception("Invalid token issuer/audience");
        }

        $hash    = hash('sha256', $token);
        $dbToken = RefreshToken::where('jti', $payload->jti)
                               ->where('token_hash', $hash)
                               ->first();

        if (!$dbToken || $dbToken->revoked || Carbon::now()->gt($dbToken->expires_at)) {
            throw new Exception("Refresh token revoked or expired");
        }

        return $payload;
    }

    /**
     * Revoke refresh token (by JTI)
     */
    public function revokeRefreshToken(string $jti): void
    {
        $token = RefreshToken::where('jti', $jti)->first();
        if ($token) {
            $token->revoked = true;
            $token->save();
        }
    }

    /**
     * Build new access + refresh token pair
     * @throws Exception
     */
    public function rotateRefreshToken(int $userId, string $role = "student"): array
    {
        $refreshToken = $this->createRefreshToken($userId);

        $header = json_decode(base64_decode(explode('.', $refreshToken)[0]));
        $kid = $header->kid ?? JwtConfig::currentKid();

        $payload = JWT::decode(
            $refreshToken,
            new Key(JwtConfig::secret($kid), 'HS256')
        );

        $accessToken  = $this->createAccessToken($userId, $role, $payload->jti);

        return [
            "access_token"  => $accessToken,
            "refresh_token" => $refreshToken,
            "token_type"    => "Bearer",
            "expires_in"    => JwtConfig::access_ttl()
        ];
    }

    public function revokeAccessByRefreshJti(string $refreshJti): void
    {
        Session::where('refresh_jti', $refreshJti)
            ->update(['revoked' => true]);
    }

    public function revokeAllTokensForUser(int $userId): void
    {
        RefreshToken::where('user_id', $userId)->update(['revoked' => true]);
        Session::where('user_id', $userId)->update(['revoked' => true]);
    }
}