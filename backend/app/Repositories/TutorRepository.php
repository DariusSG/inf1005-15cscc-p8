<?php

namespace App\Repositories;

use App\Models\Tutor;

class TutorRepository
{
    public static function all(?string $search = null): array
    {
        $q = Tutor::with('user:id,email');
        if ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('module_code', 'like', "%$search%");
            });
        }
        return $q->latest()->get()->toArray();
    }

    public static function create(array $data): Tutor
    {
        return Tutor::create($data);
    }
}