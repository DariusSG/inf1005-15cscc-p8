<?php

namespace App\Repositories;

use App\Models\Module;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Module',
    type: 'object',
    required: ["code", "name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "code", type: "string", example: "CS101"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "faculty", type: "string", nullable: true),
        new OA\Property(property: "credits", type: "integer", minimum: 1, maximum: 20),
        new OA\Property(
            property: "semesters", 
            type: "array", 
            items: new OA\Items(type: "integer", minimum: 1, maximum: 3)
        ),
        new OA\Property(
            property: "prereqs", 
            type: "array", 
            items: new OA\Items(type: "string")
        )
    ]
)]
class ModuleRepository extends BaseRepository
{
    public static function all(): array
    {
        /** @var Module $m */
        return Module::with(['semesters'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get()
            ->map(fn($m) => self::format($m))
            ->all();
    }

    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $paginator = Module::with(['semesters'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('code')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedItems = collect($paginator->items())
            ->map(fn($m) => self::format($m))
            ->all();

        return [
            'data' => $formattedItems,
            'meta' => self::buildPaginationMeta(
                $paginator->total(),
                $perPage,
                $page
            ),
        ];
    }

    public static function findByCode(string $code): ?Module
    {
        return Module::with(['prereqs', 'semesters'])
            ->where('code', $code)
            ->first();
    }

    public static function findManyByCodes(array $codes): Collection
    {
        if (empty($codes)) {
            // Return an empty collection immediately to save a DB trip
            return new Collection();
        }

        return Module::with(['prereqs', 'semesters'])
            ->whereIn('code', $codes)->get();
    }


    /**
     * Expected keys: code, name, description?, faculty?, credits?, semesters?, prereqs?
     * @throws Throwable
     */
    public static function create(array $data): Module
    {
        $semesters = $data['semesters'] ?? [];
        $prereqs = $data['prereqs'] ?? [];

        unset($data['semesters'], $data['prereqs']);

        return Capsule::connection()->transaction(function () use ($data, $semesters, $prereqs) {
            $module = Module::create($data);

            if (!empty($semesters)) {
                $module->semesters()->createMany(
                    array_map(
                        fn($semester) => ['semester' => (int) $semester],
                        $semesters
                    )
                );
            }

            if (!empty($prereqs)) {
                $module->prereqs()->sync($prereqs);
            }

            return $module->fresh(['prereqs', 'semesters']);
        });
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