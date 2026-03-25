<?php

namespace Tests\Unit;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new \ReflectionProperty(Request::class, 'context');
        $ref->setValue(null, []);
    }

    public function test_setContext_and_context(): void
    {
        Request::setContext('foo', 'bar');
        $this->assertSame('bar', Request::context('foo'));
    }

    public function test_context_returns_null_for_missing_key(): void
    {
        $this->assertNull(Request::context('nonexistent'));
    }

    public function test_context_overwrites_existing_value(): void
    {
        Request::setContext('key', 'first');
        Request::setContext('key', 'second');
        $this->assertSame('second', Request::context('key'));
    }

    public function test_method_reads_server_variable(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertSame('POST', Request::method());
    }

    public function test_uri_strips_query_string(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/modules?page=2&per_page=10';
        $this->assertSame('/api/v1/modules', Request::uri());
    }

    public function test_uri_strips_trailing_slash(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/modules/';
        $this->assertSame('/api/v1/modules', Request::uri());
    }
}
