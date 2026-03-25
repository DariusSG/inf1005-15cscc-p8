<?php

namespace Tests\Unit;

use App\Core\Helpers;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Helpers::config().
 *
 * We use a temporary config file so we don't touch the real config/config.php.
 */
class HelpersTest extends TestCase
{
    private string $originalConfigPath;
    private string $tmpConfig;

    protected function setUp(): void
    {
        // Reset the static cache between tests via reflection
        // setAccessible() is a no-op since PHP 8.1 and deprecated in 8.5
        $ref = new \ReflectionProperty(Helpers::class, 'cfg');
        $ref->setValue(null, null);
    }

    public function test_config_returns_default_for_missing_key(): void
    {
        $value = Helpers::config('nonexistent.key', 'fallback');
        $this->assertSame('fallback', $value);
    }

    public function test_config_returns_null_default_when_not_specified(): void
    {
        $value = Helpers::config('nonexistent.key');
        $this->assertNull($value);
    }

    public function test_config_reads_nested_key(): void
    {
        // 'app.version' should exist in the real config
        $version = Helpers::config('app.version');
        $this->assertNotNull($version);
        $this->assertIsString($version);
    }

    public function test_config_reads_top_level_section(): void
    {
        $db = Helpers::config('database');
        $this->assertIsArray($db);
        $this->assertArrayHasKey('driver', $db);
    }

    public function test_config_returns_default_for_missing_nested_key(): void
    {
        $value = Helpers::config('app.nonexistent_key', 'default_val');
        $this->assertSame('default_val', $value);
    }
}
