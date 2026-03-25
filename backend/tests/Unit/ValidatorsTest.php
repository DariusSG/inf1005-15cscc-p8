<?php

namespace Tests\Unit;

use App\Core\Validators;
use PHPUnit\Framework\TestCase;

class ValidatorsTest extends TestCase
{
    // ── moduleCode ────────────────────────────────────────────────────────

    public function test_moduleCode_valid(): void
    {
        $this->assertSame('INF1005', Validators::moduleCode('inf1005'));
        $this->assertSame('CS', Validators::moduleCode('cs'));
    }

    public function test_moduleCode_null_returns_null(): void
    {
        $this->assertNull(Validators::moduleCode(null));
        $this->assertNull(Validators::moduleCode(''));
    }

    public function test_moduleCode_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::moduleCode('INVALID CODE!');
    }

    public function test_moduleCode_too_long_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::moduleCode('TOOLONGCODE1');
    }

    // ── email ─────────────────────────────────────────────────────────────

    public function test_email_valid(): void
    {
        $this->assertSame('user@example.com', Validators::email('User@Example.COM'));
    }

    public function test_email_null_returns_null(): void
    {
        $this->assertNull(Validators::email(null));
        $this->assertNull(Validators::email(''));
    }

    public function test_email_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::email('not-an-email');
    }

    // ── rating ────────────────────────────────────────────────────────────

    public function test_rating_valid(): void
    {
        $this->assertSame(1, Validators::rating(1));
        $this->assertSame(5, Validators::rating(5));
        $this->assertSame(3, Validators::rating('3'));
    }

    public function test_rating_below_range_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::rating(0);
    }

    public function test_rating_above_range_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::rating(6);
    }

    // ── title ─────────────────────────────────────────────────────────────

    public function test_title_valid(): void
    {
        $this->assertSame('Hello World', Validators::title('  Hello World  '));
    }

    public function test_title_empty_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::title('');
    }

    public function test_title_too_long_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::title(str_repeat('a', 201));
    }

    // ── text ──────────────────────────────────────────────────────────────

    public function test_text_valid(): void
    {
        $this->assertSame('Hello', Validators::text('  Hello  '));
    }

    public function test_text_empty_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::text('');
    }

    public function test_text_exceeds_max_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::text(str_repeat('x', 10001));
    }

    // ── bountyAmount ──────────────────────────────────────────────────────

    public function test_bountyAmount_valid(): void
    {
        $this->assertSame(50.0, Validators::bountyAmount(50));
        $this->assertNull(Validators::bountyAmount(null));
    }

    public function test_bountyAmount_negative_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::bountyAmount(-1);
    }

    public function test_bountyAmount_exceeds_max_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::bountyAmount(10001);
    }

    // ── rate ──────────────────────────────────────────────────────────────

    public function test_rate_valid(): void
    {
        $this->assertSame(25.5, Validators::rate(25.5));
        $this->assertNull(Validators::rate(null));
    }

    public function test_rate_negative_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Validators::rate(-0.01);
    }

    // ── search ────────────────────────────────────────────────────────────

    public function test_search_strips_special_chars(): void
    {
        $result = Validators::search('hello; DROP TABLE');
        $this->assertStringNotContainsString(';', $result);
    }

    public function test_search_null_returns_null(): void
    {
        $this->assertNull(Validators::search(null));
        $this->assertNull(Validators::search(''));
    }

    public function test_search_truncates_at_100(): void
    {
        $result = Validators::search(str_repeat('a', 200));
        $this->assertLessThanOrEqual(100, strlen($result));
    }
}
