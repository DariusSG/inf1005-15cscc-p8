<?php

namespace App\Repositories;

/**
 * @internal
 */
abstract class BaseRepository
{
    /**
     * Build a standardized pagination meta array.
     *
     * @param int $total
     * @param int $perPage
     * @param int $page
     * @return array{total: int, per_page: int, current_page: int, last_page: int}
     */
    public function buildPaginationMeta(int $total, int $perPage, int $page): array
    {
        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Escape a LIKE search term for safe SQL usage.
     *
     * @param string $search Raw search string.
     * @return string Escaped string wrapped in wildcards.
     */
    public function escapeSearch(string $search): string
    {
        return '%' . addcslashes($search, '%_') . '%';
    }
}