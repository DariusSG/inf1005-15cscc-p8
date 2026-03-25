<?php

namespace App\Repositories;

use App\Models\Tutor;

class TutorRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $search = $filters['search'] ?? null;

        $query = Tutor::with(['user:id,email', 'modules:code,name']);

        if ($search) {
            $escaped = BaseRepository::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('name LIKE ?', [$escaped])
                  ->orWhereHas('modules', fn($mq) =>
                      $mq->whereRaw('code LIKE ?', [$escaped])
                         ->orWhereRaw('name LIKE ?', [$escaped])
                  );
            });
        }

        $total  = $query->count();
        $tutors = $query
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($t) => self::format($t))
            ->all();

        return [
            'data' => $tutors,
            'meta' => BaseRepository::buildPaginationMeta($total, $perPage, $page),
        ];
    }

    public static function all(?string $search = null): array
    {
        $q = Tutor::with(['user:id,email', 'modules:code,name']);

        if ($search) {
            $q->where(function ($q) use ($search) {
                $escaped = '%' . addcslashes($search, '%_') . '%';
                $q->whereRaw('name LIKE ?', [$escaped])
                  ->orWhereHas('modules', fn($mq) =>
                      $mq->whereRaw('code LIKE ?', [$escaped])
                         ->orWhereRaw('name LIKE ?', [$escaped])
                  );
            });
        }

        return $q->latest()->get()
            ->map(fn($t) => self::format($t))
            ->all();
    }

    /**
     * Create a tutor listing.
     * $data may include 'module_codes' (array of strings) for the pivot.
     */
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