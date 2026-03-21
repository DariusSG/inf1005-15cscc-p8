<?php

namespace App\Services;

use App\Models\EmailVerification;
use App\Config\Mail;
use Carbon\Carbon;

class VerificationService
{
    private const SIT_DOMAIN = '@sit.singaporetech.edu.sg';

    private MailService $mailer;

    public function __construct(MailService $mailer)
    {
        $this->mailer = $mailer;
    }

    // ── Domain guard ────────────────────────────────────────────────────

    public function assertSitEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        if (!str_ends_with(strtolower($email), self::SIT_DOMAIN)) {
            throw new \InvalidArgumentException(
                'Only SIT student emails ending in ' . self::SIT_DOMAIN . ' are accepted.'
            );
        }
    }

    // ── Send invite ─────────────────────────────────────────────────────

    /**
     * Invalidate any existing unused tokens for this email,
     * create a new one, and send the invite.
     */
    public function sendInvite(string $email): void
    {
        $this->assertSitEmail($email);

        // Invalidate previous tokens for this email
        EmailVerification::where('email', $email)
            ->where('used', false)
            ->update(['used' => true]);

        // Generate a cryptographically random raw token
        $rawToken  = bin2hex(random_bytes(32));         // 64 hex chars
        $storedHash = hash('sha256', $rawToken);        // store hash, send raw

        EmailVerification::create([
            'email'      => strtolower($email),
            'token'      => $storedHash,
            'expires_at' => Carbon::now()->addSeconds(Mail::verifyTtl()),
            'used'       => false,
        ]);

        $this->mailer->sendVerificationEmail($email, $rawToken);
    }

    // ── Verify token ────────────────────────────────────────────────────

    /**
     * Look up a raw token. Returns the verified email on success.
     * Throws on invalid / expired / already-used token.
     */
    public function verifyToken(string $rawToken): string
    {
        $hash = hash('sha256', $rawToken);

        $record = EmailVerification::where('token', $hash)
            ->where('used', false)
            ->first();

        if (!$record) {
            throw new \RuntimeException('Invalid or already-used verification link.');
        }

        if (Carbon::now()->gt($record->expires_at)) {
            throw new \RuntimeException('This verification link has expired. Please request a new one.');
        }

        return $record->email;
    }

    // ── Consume token ───────────────────────────────────────────────────

    /** Mark the token as used (call after account is successfully created). */
    public function consumeToken(string $rawToken): void
    {
        $hash = hash('sha256', $rawToken);
        EmailVerification::where('token', $hash)->update(['used' => true]);
    }
}