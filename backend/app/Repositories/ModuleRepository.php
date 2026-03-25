<?php

namespace App\Repositories;

use App\Models\Module;

class ModuleRepository
{
    public static function all(): array
    {
        return Module::with(['semesters'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get()
            ->map(fn($m) => self::format($m))
            ->all();
    }

    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $total   = Module::count();
        $modules = Module::with(['semesters'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('code')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($m) => self::format($m));

        return [
            'data' => $modules->all(),
            'meta' => BaseRepository::buildPaginationMeta($total, $perPage, $page),
        ];
    }

    public static function findByCode(string $code): ?Module
    {
        return Module::with(['prereqs', 'semesters'])
            ->where('code', $code)
            ->first();
    }

    public static function format(Module $module): array
    {
        $data = $module->toArray();

        // Flatten semesters to a plain int array: [1, 2]
        $data['semesters'] = $module->semesters
            ->pluck('semester')
            ->values()
            ->all();

        // prereqs as array of module codes
        if ($module->relationLoaded('prereqs')) {
            $data['prereqs'] = $module->prereqs
                ->pluck('code')
                ->values()
                ->all();
        }

        return $data;
    }
}