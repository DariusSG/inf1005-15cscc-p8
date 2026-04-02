<?php

namespace App\Repositories;

use App\Models\Tutor;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Tutor",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "user_email", type: "string", nullable: true),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "modules", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "rate", type: "number", format: "float"),
        new OA\Property(property: "contact_email", type: "string", format: "email"),
        new OA\Property(property: "bio", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
class TutorRepository extends BaseRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $moduleCode = $filters['module_code'] ?? null;
        $search     = $filters['search'] ?? null;
        $authorId   = $filters['author_id'] ?? null;

        $query = Tutor::with(['user:id,email', 'modules:code,name']);

        if ($moduleCode) {
            $query->whereHas('modules', fn($q) => $q->where('code', $moduleCode));
        }

        if ($authorId) {
            $query->where('user_id', $authorId);
        }

        if ($search) {
            $escaped = self::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                    ->orWhereRaw('contact_email LIKE ?', [$escaped])
                    ->orWhereRaw('bio LIKE ?', [$escaped])
                    ->orWhereHas('modules', fn($mq) =>
                    $mq->whereRaw('code LIKE ?', [$escaped])
                        ->orWhereRaw('name LIKE ?', [$escaped])
                        ->orWhereRaw('description LIKE ?', [$escaped])
                    );
            });
        }

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $tutors = collect($paginator->items())
            ->map(fn($t) => self::format($t))
            ->all();

        return [
            'data' => $tutors,
            'meta' => self::buildPaginationMeta(
                $paginator->total(),
                $perPage,
                $page
            ),
        ];
    }

    public static function all(?string $search = null): array
    {
        $q = Tutor::with(['user:id,email', 'modules:code,name']);

        if ($search) {
            $escaped = self::escapeSearch($search);
            $q->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                  ->orWhereRaw('contact_email LIKE ?', [$escaped])
                  ->orWhereRaw('bio LIKE ?', [$escaped])
                  ->orWhereHas('modules', fn($mq) =>
                      $mq->whereRaw('code LIKE ?', [$escaped])
                         ->orWhereRaw('name LIKE ?', [$escaped])
                         ->orWhereRaw('description LIKE ?', [$escaped])
                  );
            });
        }

        /** @var Tutor $t */
        return $q->latest()->get()
            ->map(fn($t) => self::format($t))
            ->all();
    }

    /**
     * Create a tutor listing.
     * $data may include 'module_codes' (array of strings) for the pivot.
     */
    public static function find(int $id): ?Tutor
    {
        return Tutor::with(['user:id,email', 'modules:code,name'])->find($id);
    }

    public static function update(int $id, array $data, ?array $moduleCodes = null): Tutor
    {
        $tutor = Tutor::findOrFail($id);
        if (!empty($data)) {
            $tutor->update($data);
        }
        if ($moduleCodes !== null) {
            $tutor->modules()->sync(array_map('strtoupper', $moduleCodes));
        }
        return $tutor->fresh(['user:id,email', 'modules:code,name']);
    }

    public static function create(array $data): Tutor
    {
        $moduleCodes = $data['module_codes'] ?? [];
        unset($data['module_codes']);

        $tutor = Tutor::create($data);

        if (!empty($moduleCodes)) {
            $tutor->modules()->sync(array_map('strtoupper', $moduleCodes));
        }

        return $tutor->load(['user:id,email', 'modules:code,name']);
    }

    public static function format(Tutor $tutor): array
    {
        return [
            'id'           => $tutor->id,
            'userEmail'    => $tutor->user?->email,
            'name'         => $tutor->name,
            'modules'      => $tutor->modules->pluck('code')->values()->all(),
            'rate'         => $tutor->rate,
            'contactEmail' => $tutor->contact_email,
            'bio'          => $tutor->bio,
        ];
    }
}