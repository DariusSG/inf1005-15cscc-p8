<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWTException;
use Firebase\JWT\Key;
use App\Config\JwtConfig;
use App\Models\Session;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\RefreshToken;
use Carbon\Carbon;

class TokenService
{
    private const ISSUER = "sitizen-api";
    private const AUDIENCE = "sitizen-client";

    private const BIND_TO_IP = true;

    /**
     * Create access token, store JTI in sessions table
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
            'ip'         => self::BIND_TO_IP ? ($_SERVER['REMOTE_ADDR'] ?? null) : null,
            'user_agent' => self::BIND_TO_IP ? ($_SERVER['HTTP_USER_AGENT'] ?? null) : null,
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
            throw new \Exception("Invalid token issuer/audience");
        }

        $session = Session::where('jti', $payload->jti)->first();
        if ($session && self::BIND_TO_IP) {
            $clientIp = self::normalizeIp($_SERVER['REMOTE_ADDR'] ?? '');
            $tokenIp = self::normalizeIp($session->ip ?? '');
            
            if ($tokenIp && $clientIp !== $tokenIp) {
                throw new \Exception("Token used from unexpected location");
            }
        }

        $session = Session::where('jti', $payload->jti)->first();
        if (!$session || $session->revoked || strtotime($session->expires_at) < time()) {
            throw new \Exception("Access token revoked or expired");
        }

        return $payload;
    }

    /**
     * Normalize IP address for comparison
     * Handles IPv4-mapped IPv6 addresses
     */
    private static function normalizeIp(string $ip): string
    {
        // Handle empty IP
        if (empty($ip)) {
            return '';
        }
        
        // Check for IPv4-mapped IPv6 (::ffff:192.168.1.1)
        if (str_starts_with($ip, '::ffff:')) {
            $ip = substr($ip, 7);
        }
        
        return $ip;
    }

    /**
     * Revoke access token (by JTI)
     */
    public function revokeAccessToken(string $jti)
    {
        $session = Session::where('jti', $jti)->first();
        if ($session) {
            $session->revoked = true;
            $session->save();
        }
    }

    /**
     * Create refresh token, store in DB for rotation
     */
    public function createRefreshToken(int $userId): string
    {
        $jti = bin2hex(random_bytes(16));
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
                throw new \Exception("Invalid token issuer/audience");
            }

            $dbToken = RefreshToken::where('jti', $payload->jti)
                                   ->where('token_hash', $hash)
                                   ->lockForUpdate()  // Lock row for atomic update
                                   ->first();

            if (!$dbToken || $dbToken->revoked || Carbon::now()->gt($dbToken->expires_at)) {
                if ($dbToken && $dbToken->revoked) {
                    Session::where('refresh_jti', $payload->jti)->update(['revoked' => true]);
                }
                throw new \Exception("Refresh token revoked or expired");
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
            throw new \Exception("Invalid token issuer/audience");
        }

        $hash    = hash('sha256', $token);
        $dbToken = RefreshToken::where('jti', $payload->jti)
                               ->where('token_hash', $hash)
                               ->first();

        if (!$dbToken || $dbToken->revoked || Carbon::now()->gt($dbToken->expires_at)) {
            throw new \Exception("Refresh token revoked or expired");
        }

        return $payload;
    }

    /**
     * Revoke refresh token (by JTI)
     */
    public function revokeRefreshToken(string $jti)
    {
        $token = RefreshToken::where('jti', $jti)->first();
        if ($token) {
            $token->revoked = true;
            $token->save();
        }
    }

    /**
     * Build new access + refresh token pair
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

    public function revokeAccessByRefreshJti(string $refreshJti)
    {
        Session::where('refresh_jti', $refreshJti)
            ->update(['revoked' => true]);
    }
}