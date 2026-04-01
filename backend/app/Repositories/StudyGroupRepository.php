<?php

namespace App\Repositories;

use App\Models\StudyGroup;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StudyGroup",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "user_id", type: "integer"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "module_code", type: "string"),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "meeting_time", type: "string", nullable: true),
        new OA\Property(property: "location", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
class StudyGroupRepository extends BaseRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $search = $filters['search'] ?? null;

        $query = StudyGroup::with('creator:id,name,email');

        if ($search) {
            $escaped = self::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                    ->orWhereRaw('module_code LIKE ?', [$escaped]);
            });
        }

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $groups = collect($paginator->items())->toArray();

        return [
            'data' => $groups,
            'meta' => self::buildPaginationMeta(
                $paginator->total(),
                $perPage,
                $page
            ),
        ];
    }

    public static function all(?string $search = null): array
    {
        $q = StudyGroup::with('creator:id,name,email');
        if ($search) {
            $q->where(function ($q) use ($search) {
                $escaped = self::escapeSearch($search);
                $q->whereRaw('name LIKE ?', [$escaped])
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