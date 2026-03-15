<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\JwtConfig;
use App\Models\Session;
use App\Models\RefreshToken;
use Carbon\Carbon;

class TokenService
{
    private const ISSUER = "sitizen-api";
    private const AUDIENCE = "sitizen-client";

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
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
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
        if (!$session || $session->revoked || strtotime($session->expires_at) < time()) {
            throw new \Exception("Access token revoked or expired");
        }

        return $payload;
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
     * Verify refresh token, check DB for rotation
     */
    public function verifyRefreshToken(string $token): object
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

        $hash = hash('sha256', $token);
        $dbToken = RefreshToken::where('jti', $payload->jti)->where('token_hash', $hash)->first();
        if (!$dbToken || $dbToken->revoked || strtotime($dbToken->expires_at) < time()) {
            if ($dbToken && $dbToken->revoked) {
                // replay attack detected → revoke entire session
                Session::where('refresh_jti', $payload->jti)
                    ->update(['revoked' => true]);
            }
   
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
    public function rotateRefreshToken(int $userId, string $role = "user"): array
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