<?php

namespace App\Repositories;

use App\Models\StudyGroup;

class StudyGroupRepository
{
    public static function all(?string $search = null): array
    {
        $q = StudyGroup::with('creator:id,email');
        if ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('module_code', 'like', "%$search%");
            });
        }
        return $q->latest()->get()->toArray();
    }

    public static function create(array $data): StudyGroup
    {
        return StudyGroup::create($data);
    }
}