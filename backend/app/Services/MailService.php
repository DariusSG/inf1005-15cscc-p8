<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Config\Mail;
use App\Core\Logger;
use RuntimeException;

class MailService
{
    /**
     * @throws Exception
     */
    private function mailer(): PHPMailer
    {
        $cfg    = Mail::config();
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host       = $cfg['host'];
        $mailer->SMTPAuth   = true;
        $mailer->Username   = $cfg['username'];
        $mailer->Password   = $cfg['password'];
        $mailer->SMTPSecure = $cfg['encryption'];
        $mailer->Port       = $cfg['port'];
        $mailer->CharSet    = 'UTF-8';

        $mailer->setFrom($cfg['from_email'], $cfg['from_name']);

        return $mailer;
    }

    /**
     * Send the registration invite email.
     *
     * @param string $toEmail  Recipient SIT email
     * @param string $token    Raw (un-hashed) verification token
     */
    public function sendVerificationEmail(string $toEmail, string $token): void
    {
        $link = Mail::appUrl() . '/register/complete?token=' . urlencode($token);
        $ttlH = Mail::verifyTtl() / 3600;

        $html = $this->verificationTemplate($toEmail, $link, $ttlH);

        try {
            $mailer = $this->mailer();
            $mailer->addAddress($toEmail);
            $mailer->isHTML();
            $mailer->Subject = 'Complete your SITizen registration';
            $mailer->Body    = $html;
            $mailer->AltBody = "Complete your registration by visiting: $link\n\nThis link expires in $ttlH hour(s).";
            $mailer->send();
        } catch (Exception $e) {
            Logger::channel()->error('MailService::sendVerificationEmail failed', [
                'to'    => $toEmail,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to send verification email. Please try again later.');
        }
    }

        public function sendPasswordResetEmail(string $toEmail, string $token): void
        {
          $link = Mail::appUrl() . '/reset-password?token=' . urlencode($token);
          $ttlM = (int) floor(Mail::resetTtl() / 60);

          $html = $this->passwordResetTemplate($toEmail, $link, $ttlM);

          try {
            $mailer = $this->mailer();
            $mailer->addAddress($toEmail);
            $mailer->isHTML();
            $mailer->Subject = 'Reset your SITizen password';
            $mailer->Body    = $html;
            $mailer->AltBody = "Reset your password by visiting: $link\n\nThis link expires in $ttlM minute(s).";
            $mailer->send();
          } catch (Exception $e) {
            Logger::channel()->error('MailService::sendPasswordResetEmail failed', [
              'to'    => $toEmail,
              'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to send password reset email. Please try again later.');
          }
        }

    private function verificationTemplate(string $email, string $link, float $ttlH): string
    {
        $ttlLabel = $ttlH >= 1 ? (int)$ttlH . ' hour' . ($ttlH > 1 ? 's' : '') : '60 minutes';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
          <title>Verify your SITizen email</title>
        </head>
        <body style="margin:0;padding:0;background:#f4f4f5;font-family:system-ui,-apple-system,sans-serif;">
          <table style="width:100%;border-spacing:0;background:#f4f4f5;padding:40px 0;">
            <tr>
              <td style="text-align:center;">
                <table style="width:560px;background:#ffffff;border-radius:12px;overflow:hidden;
                              box-shadow:0 1px 4px rgba(0,0,0,.08);padding:0;border-spacing:0;">

                  <!-- Header -->
                  <tr>
                    <td style="background:#1d4ed8;padding:32px 40px;">
                      <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;
                                 letter-spacing:-0.3px;">SITizen</h1>
                      <p  style="margin:4px 0 0;color:#bfdbfe;font-size:13px;">
                        Singapore Institute of Technology Student Hub
                      </p>
                    </td>
                  </tr>

                  <!-- Body -->
                  <tr>
                    <td style="padding:40px 40px 32px;">
                      <h2 style="margin:0 0 12px;font-size:20px;color:#111827;font-weight:600;">
                        Complete your registration
                      </h2>
                      <p style="margin:0 0 8px;color:#374151;font-size:15px;line-height:1.6;">
                        Hi there! We received a request to create a SITizen account for
                        <strong>$email</strong>.
                      </p>
                      <p style="margin:0 0 28px;color:#374151;font-size:15px;line-height:1.6;">
                        Click the button below to set your name and password and activate
                        your account. This link expires in <strong>$ttlLabel</strong>.
                      </p>

                      <!-- CTA -->
                      <table style="padding:0;border-spacing:0;">
                        <tr>
                          <td style="background:#1d4ed8;border-radius:8px;">
                            <a href="$link"
                               style="display:inline-block;padding:14px 28px;color:#ffffff;
                                      font-size:15px;font-weight:600;text-decoration:none;
                                      letter-spacing:1px;">
                              Verify email &amp; continue →
                            </a>
                          </td>
                        </tr>
                      </table>

                      <!-- Fallback link -->
                      <p style="margin:24px 0 0;font-size:12px;color:#6b7280;line-height:1.6;">
                        Button not working? Paste this URL into your browser:<br/>
                        <a href="$link" style="color:#1d4ed8;word-break:break-all;">$link</a>
                      </p>
                    </td>
                  </tr>

                  <!-- Footer -->
                  <tr>
                    <td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;">
                      <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                        If you didn't request this, you can safely ignore this email.
                        This link will expire automatically.<br/>
                        © SITizen — for SIT students, by SIT students.
                      </p>
                    </td>
                  </tr>

                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>
        HTML;
    }

    private function passwordResetTemplate(string $email, string $link, int $ttlM): string
    {
        $ttlLabel = $ttlM === 60 ? '1 hour' : $ttlM . ' minutes';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
          <title>Reset your SITizen password</title>
        </head>
        <body style="margin:0;padding:0;background:#f4f4f5;font-family:system-ui,-apple-system,sans-serif;">
          <table style="width:100%;border-spacing:0;background:#f4f4f5;padding:40px 0;">
            <tr>
              <td style="text-align:center;">
                <table style="width:560px;background:#ffffff;border-radius:12px;overflow:hidden;
                              box-shadow:0 1px 4px rgba(0,0,0,.08);padding:0;border-spacing:0;">
                  <tr>
                    <td style="background:#1d4ed8;padding:32px 40px;">
                      <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;
                                 letter-spacing:-0.3px;">SITizen</h1>
                      <p style="margin:4px 0 0;color:#bfdbfe;font-size:13px;">
                        Singapore Institute of Technology Student Hub
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:40px 40px 32px;">
                      <h2 style="margin:0 0 12px;font-size:20px;color:#111827;font-weight:600;">
                        Password reset requested
                      </h2>
                      <p style="margin:0 0 8px;color:#374151;font-size:15px;line-height:1.6;">
                        We received a request to reset the password for <strong>$email</strong>.
                      </p>
                      <p style="margin:0 0 28px;color:#374151;font-size:15px;line-height:1.6;">
                        Click the button below to choose a new password. This link expires in
                        <strong>$ttlLabel</strong>.
                      </p>

                      <table style="padding:0;border-spacing:0;">
                        <tr>
                          <td style="background:#1d4ed8;border-radius:8px;">
                            <a href="$link"
                               style="display:inline-block;padding:14px 28px;color:#ffffff;
                                      font-size:15px;font-weight:600;text-decoration:none;
                                      letter-spacing:1px;">
                              Reset password
                            </a>
                          </td>
                        </tr>
                      </table>

                      <p style="margin:24px 0 0;font-size:12px;color:#6b7280;line-height:1.6;">
                        Button not working? Paste this URL into your browser:<br/>
                        <a href="$link" style="color:#1d4ed8;word-break:break-all;">$link</a>
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;">
                      <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                        If you didn't request a password reset, you can safely ignore this email.
                        Your password will stay the same.
                      </p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>
        HTML;
    }
}