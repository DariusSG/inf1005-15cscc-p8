<?php

namespace App\Repositories;

use App\Models\User;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: "id", type: "integer", description: "User ID"),
        new OA\Property(property: "email", type: "string"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "role", type: "string")
    ]
)]
class UserRepository extends BaseRepository
{

    public static function findByEmail(string $email): ?User
    {
        return User::select('id', 'email', 'name', 'password', 'role')
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();
    }

    public static function findById(int $id): ?User
    {
        return User::select('id', 'email', 'name', 'role')->find($id);
    }

    public static function findByIdWithTrashed(int $id): ?User
    {
        return User::withTrashed()->select('id', 'email', 'name', 'role', 'deleted_at')->find($id);
    }

    public static function findByIdWithPassword(int $id): ?User
    {
        return User::select('id', 'email', 'name', 'password', 'role')->find($id);
    }

    public static function create(
        string $email,
        string $password,
        string $role = 'student',
        string $name = ''
    ): User {
        return User::create([
            'email'    => strtolower(trim($email)),
            'name'     => $name,
            'password' => $password,
            'role'     => $role,
        ]);
    }

    public static function paginate(int $perPage = 20, int $page = 1): array
    {
        $paginator = User::select('id', 'email', 'name', 'role')
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $users = collect($paginator->items())->toArray();

        return [
            'data' => $users,
            'meta' => self::buildPaginationMeta(
                $paginator->total(),
                $perPage,
                $page
            ),
        ];
    }

    public static function delete(User $user): void
    {
        $user->delete();
        $user->forceDelete();
    }
}