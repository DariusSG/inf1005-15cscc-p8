<?php

namespace Tests\Unit;

use App\Repositories\BaseRepository;
use PHPUnit\Framework\TestCase;

class BaseRepositoryTest extends TestCase
{
    // ── buildPaginationMeta ───────────────────────────────────────────────

    public function test_buildPaginationMeta_first_page(): void
    {
        $meta = BaseRepository::buildPaginationMeta(100, 20, 1);

        $this->assertSame(100, $meta['total']);
        $this->assertSame(20,  $meta['per_page']);
        $this->assertSame(1,   $meta['current_page']);
        $this->assertSame(5,   $meta['last_page']);
    }

    public function test_buildPaginationMeta_last_page(): void
    {
        $meta = BaseRepository::buildPaginationMeta(100, 20, 5);
        $this->assertSame(5, $meta['current_page']);
        $this->assertSame(5, $meta['last_page']);
    }

    public function test_buildPaginationMeta_partial_last_page(): void
    {
        // 21 items, 20 per page → 2 pages
        $meta = BaseRepository::buildPaginationMeta(21, 20, 1);
        $this->assertSame(2, $meta['last_page']);
    }

    public function test_buildPaginationMeta_zero_total(): void
    {
        $meta = BaseRepository::buildPaginationMeta(0, 20, 1);
        $this->assertSame(0, $meta['total']);
        $this->assertSame(1, $meta['last_page']); // at least 1 page
    }

    public function test_buildPaginationMeta_exact_division(): void
    {
        $meta = BaseRepository::buildPaginationMeta(40, 20, 2);
        $this->assertSame(2, $meta['last_page']);
    }

    // ── escapeSearch ──────────────────────────────────────────────────────

    public function test_escapeSearch_wraps_with_wildcards(): void
    {
        $result = BaseRepository::escapeSearch('hello');
        $this->assertSame('%hello%', $result);
    }

    public function test_escapeSearch_escapes_percent(): void
    {
        $result = BaseRepository::escapeSearch('100%');
        $this->assertStringContainsString('\%', $result);
    }

    public function test_escapeSearch_escapes_underscore(): void
    {
        $result = BaseRepository::escapeSearch('a_b');
        $this->assertStringContainsString('\_', $result);
    }

    public function test_escapeSearch_empty_string(): void
    {
        $result = BaseRepository::escapeSearch('');
        $this->assertSame('%%', $result);
    }
}
