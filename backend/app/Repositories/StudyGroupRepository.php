<?php

namespace App\Repositories;

use App\Models\StudyGroup;

class StudyGroupRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $search = $filters['search'] ?? null;

        $query = StudyGroup::with('creator:id,email');

        if ($search) {
            $escaped = BaseRepository::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                  ->orWhereRaw('module_code LIKE ?', [$escaped]);
            });
        }

        $total  = $query->count();
        $groups = $query
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        return [
            'data' => $groups,
            'meta' => BaseRepository::buildPaginationMeta($total, $perPage, $page),
        ];
    }

    public static function all(?string $search = null): array
    {
        $q = StudyGroup::with('creator:id,email');
        if ($search) {
            $q->where(function ($q) use ($search) {
                $escaped = '%' . addcslashes($search, '%_') . '%';
                $q->whereRaw('title LIKE ?', [$escaped])
                  ->orWhereRaw('module_code LIKE ?', [$escaped]);
            });
        }
        return $q->latest()->get()->toArray();
    }

    public static function create(array $data): StudyGroup
    {
        return StudyGroup::create($data);
    }
}