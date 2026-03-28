<?php

namespace App\Services;

use App\Core\Logger;
use App\Core\Request;

class AuditService
{
    public const string EVENT_LOGIN_SUCCESS = 'auth.login.success';
    public const string EVENT_LOGIN_FAILED = 'auth.login.failed';
    public const string EVENT_LOGOUT = 'auth.logout';
    public const string EVENT_REFRESH_TOKEN = 'auth.refresh';
    public const string EVENT_REVIEW_CREATE = 'review.create';
    public const string EVENT_REVIEW_UPDATE = 'review.update';
    public const string EVENT_REVIEW_VOTE = 'review.vote';
    public const string EVENT_REVIEW_REPORT = 'review.report';
    public const string EVENT_ADMIN_ACTION = 'admin.action';

    public static function log(string $event, array $data = []): void
    {
        $entry = [
            'event' => $event,
            'user_id' => Request::context('user_id'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'timestamp' => date('c'),
            'data' => $data,
        ];

        Logger::channel()->info("AUDIT: $event", $entry);
    }

    private static function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        return substr($local, 0, 2) . '***@' . $domain;
    }

    public static function logLogin(string $email, bool $success, ?string $reason = null): void
    {
        self::log($success ? self::EVENT_LOGIN_SUCCESS : self::EVENT_LOGIN_FAILED, [
            'email' => self::maskEmail($email),
            'reason' => $reason,
        ]);
    }

    public static function logReviewAction(string $action, int $reviewId, array $extra = []): void
    {
        self::log($action, array_merge([
            'review_id' => $reviewId,
        ], $extra));
    }
}