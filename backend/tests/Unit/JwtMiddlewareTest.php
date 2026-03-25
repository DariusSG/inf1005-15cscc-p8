<?php

namespace Tests\Unit;

use App\Core\Request;
use App\Middleware\JwtMiddleware;
use PHPUnit\Framework\TestCase;

class JwtMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset request context between tests
        $ref = new \ReflectionProperty(Request::class, 'context');
        $ref->setValue(null, []);
    }

    public function test_userId_returns_null_when_not_set(): void
    {
        $this->assertNull(JwtMiddleware::userId());
    }

    public function test_userRole_returns_null_when_not_set(): void
    {
        $this->assertNull(JwtMiddleware::userRole());
    }

    public function test_getPayload_returns_null_when_not_set(): void
    {
        $this->assertNull(JwtMiddleware::getPayload());
    }

    public function test_userId_returns_value_after_context_set(): void
    {
        Request::setContext('user_id', 42);
        $this->assertSame(42, JwtMiddleware::userId());
    }

    public function test_userRole_returns_value_after_context_set(): void
    {
        Request::setContext('user_role', 'admin');
        $this->assertSame('admin', JwtMiddleware::userRole());
    }
}
