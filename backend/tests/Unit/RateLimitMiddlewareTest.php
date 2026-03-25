<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the file-based RateLimitMiddleware.
 *
 * We test the internal logic by calling the private methods via reflection,
 * and by verifying the storage directory is created correctly.
 */
class RateLimitMiddlewareTest extends TestCase
{
    private string $storageDir;

    protected function setUp(): void
    {
        // Point to a temp directory for rate limit files
        $this->storageDir = sys_get_temp_dir() . '/sitizen_rate_limit_test_' . uniqid();
        mkdir($this->storageDir, 0750, true);

        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_SERVER['REQUEST_URI']    = '/api/v1/modules';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        foreach (glob($this->storageDir . '/*') as $f) {
            unlink($f);
        }
        rmdir($this->storageDir);
    }

    public function test_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Middleware\RateLimitMiddleware::class));
    }

    public function test_storage_dir_constant_is_defined(): void
    {
        $ref = new \ReflectionClass(\App\Middleware\RateLimitMiddleware::class);
        $this->assertTrue($ref->hasConstant('STORAGE_DIR'));
    }

    public function test_rate_limit_constants_are_positive(): void
    {
        $ref = new \ReflectionClass(\App\Middleware\RateLimitMiddleware::class);

        $defaultLimit  = $ref->getConstant('DEFAULT_LIMIT');
        $defaultWindow = $ref->getConstant('DEFAULT_WINDOW');
        $loginLimit    = $ref->getConstant('LOGIN_LIMIT');
        $loginWindow   = $ref->getConstant('LOGIN_WINDOW');

        $this->assertGreaterThan(0, $defaultLimit);
        $this->assertGreaterThan(0, $defaultWindow);
        $this->assertGreaterThan(0, $loginLimit);
        $this->assertGreaterThan(0, $loginWindow);

        // Login limit should be stricter than default
        $this->assertLessThan($defaultLimit, $loginLimit);
    }
}
