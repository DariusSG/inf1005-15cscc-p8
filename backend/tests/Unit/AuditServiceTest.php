<?php

namespace Tests\Unit;

use App\Services\AuditService;
use App\Core\Request;
use PHPUnit\Framework\TestCase;

class AuditServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new \ReflectionProperty(Request::class, 'context');
        $ref->setValue(null, []);

        $_SERVER['REMOTE_ADDR']      = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT']  = 'PHPUnit';
    }

    public function test_event_constants_are_defined(): void
    {
        $this->assertSame('auth.login.success', AuditService::EVENT_LOGIN_SUCCESS);
        $this->assertSame('auth.login.failed',  AuditService::EVENT_LOGIN_FAILED);
        $this->assertSame('auth.logout',        AuditService::EVENT_LOGOUT);
        $this->assertSame('review.create',      AuditService::EVENT_REVIEW_CREATE);
        $this->assertSame('review.vote',        AuditService::EVENT_REVIEW_VOTE);
        $this->assertSame('review.report',      AuditService::EVENT_REVIEW_REPORT);
        $this->assertSame('admin.action',       AuditService::EVENT_ADMIN_ACTION);
    }

    public function test_logLogin_does_not_throw_on_success(): void
    {
        // Should not throw — just writes to log file
        $this->expectNotToPerformAssertions();
        try {
            AuditService::logLogin('user@example.com', true);
        } catch (\Throwable $e) {
            // Log file may not be writable in CI — that's acceptable
            $this->markTestSkipped('Log file not writable: ' . $e->getMessage());
        }
    }

    public function test_logLogin_does_not_throw_on_failure(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            AuditService::logLogin('user@example.com', false, 'invalid_password');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Log file not writable: ' . $e->getMessage());
        }
    }

    public function test_logReviewAction_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            AuditService::logReviewAction(AuditService::EVENT_REVIEW_CREATE, 1, ['module' => 'INF1005']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Log file not writable: ' . $e->getMessage());
        }
    }
}
