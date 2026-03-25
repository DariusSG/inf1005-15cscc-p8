<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    abstract protected function getModel(): Model;

    /**
     * Build a standardised pagination meta array.
     * Used by all static repository paginate() methods to avoid duplication.
     */
    public static function buildPaginationMeta(int $total, int $perPage, int $page): array
    {
        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Escape a LIKE search term (wildcards only, no SQL injection).
     */
    public static function escapeSearch(string $search): string
    {
        return '%' . addcslashes($search, '%_') . '%';
    }
}