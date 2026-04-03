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
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "author", type: "string", nullable: true)
    ]
)]
class StudyGroupRepository extends BaseRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $moduleCode = $filters['module_code'] ?? null;
        $search     = $filters['search'] ?? null;
        $authorId   = $filters['author_id'] ?? null;

        $query = StudyGroup::with(['creator:id,name,email', 'module:code,name', 'members:id'])
            ->withCount('members');

        if ($moduleCode) {
            $query->where('module_code', $moduleCode);
        }

        if ($authorId) {
            $query->where('user_id', $authorId);
         }

        if ($search) {
            $escaped = self::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                ->orWhereRaw('module_code LIKE ?', [$escaped])
                ->orWhereRaw('description LIKE ?', [$escaped])
                ->orWhereRaw('location LIKE ?', [$escaped])
                ->orWhereRaw('meeting_time LIKE ?', [$escaped])
                ->orWhereHas('module', fn($mq) => $mq
                    ->whereRaw('code LIKE ?', [$escaped])
                    ->orWhereRaw('name LIKE ?', [$escaped])
                    ->orWhereRaw('description LIKE ?', [$escaped])
                );
            });
        }

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $currentUserId = $filters['current_user_id'] ?? null;

        $groups = collect($paginator->items())
            ->map(fn($g) => self::format($g, $currentUserId))
            ->all();

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
        $q = StudyGroup::with(['creator:id,name,email', 'module:code,name'])
            ->withCount('members');
        if ($search) {
            $escaped = self::escapeSearch($search);
            $q->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                  ->orWhereRaw('module_code LIKE ?', [$escaped])
                  ->orWhereRaw('description LIKE ?', [$escaped])
                  ->orWhereRaw('location LIKE ?', [$escaped])
                  ->orWhereRaw('meeting_time LIKE ?', [$escaped])
                  ->orWhereHas('module', fn($mq) => $mq
                      ->whereRaw('code LIKE ?', [$escaped])
                      ->orWhereRaw('name LIKE ?', [$escaped])
                      ->orWhereRaw('description LIKE ?', [$escaped])
                  );
            });
        }
        return $q->latest()->get()->map(fn($g) => self::format($g))->all();
    }

    public static function update(int $id, array $data): StudyGroup
    {
        $group = StudyGroup::findOrFail($id);
        if (!empty($data)) {
            $group->update($data);
        }
        return self::find($id);
    }

    public static function create(array $data): StudyGroup
    {
        $group = StudyGroup::create($data);
        $group->members()->syncWithoutDetaching([$group->user_id]);
        return $group->fresh(['creator:id,name,email', 'module:code,name'])->loadCount('members');
    }

    public static function find(int $id): ?StudyGroup
    {
        return StudyGroup::with(['creator:id,name,email', 'module:code,name'])
            ->withCount('members')
            ->find($id);
    }

    public static function join(int $studyGroupId, int $userId): array
    {
        $group = self::find($studyGroupId);
        if (!$group) {
            return ['group' => null, 'joined' => false];
        }

        $changes = $group->members()->syncWithoutDetaching([$userId]);
        $group = self::find($studyGroupId);

        return [
            'group' => self::format($group),
            'joined' => !empty($changes['attached']),
        ];
    }

    public static function leave(int $studyGroupId, int $userId): array
    {
        $group = self::find($studyGroupId);
        if (!$group) {
            return ['group' => null, 'left' => false];
        }

        $removed = $group->members()->detach($userId);
        $group = self::find($studyGroupId);

        return [
            'group' => self::format($group),
            'left' => $removed > 0,
        ];
    }

    public static function delete(int $id): bool
    {
        $group = StudyGroup::find($id);
        if (!$group) {
            return false;
        }

        return (bool) $group->delete();
    }

    public static function format(StudyGroup $group, ?int $currentUserId = null): array
    {
        $memberIds = $group->members->pluck('id')->all();

        return [
            'id' => $group->id,
            'user_id' => $group->creator ? $group->user_id : -1,
            'name' => $group->name,
            'author' => $group->creator?->name,
            'module_code' => $group->module_code,
            'module_name' => $group->module?->name,
            'description' => $group->description,
            'meeting_time' => $group->meeting_time,
            'location' => $group->location,
            'member_count' => (int) ($group->members_count ?? 0),
            'memberCount' => (int) ($group->members_count ?? 0),
            'maxSize' => (int) ($group->max_size ?? 0),
            'max_size' => (int) ($group->max_size ?? 0),
            'is_member' => $currentUserId !== null && in_array($currentUserId, $memberIds, true),
            'created_at' => $group->created_at,
        ];
    }
}