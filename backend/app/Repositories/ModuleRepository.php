<?php

namespace App\Repositories;

use App\Models\Module;

class ModuleRepository
{
    public static function all()
    {
        return Module::withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();
    }

    public static function findByCode(string $code): ?Module
    {
        return Module::where('code', $code)->first();
    }
}