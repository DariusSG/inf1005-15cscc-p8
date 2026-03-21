<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public static function findByEmail(string $email): ?User
    {
        return User::select('id', 'email', 'name', 'password', 'role')
            ->where('email', $email)
            ->first();
    }

    public static function findById(int $id): ?User
    {
        return User::select('id', 'email', 'name', 'role')->find($id);
    }

    public static function create(string $email, string $password, string $role = 'student', string $name = ''): User
    {
        return User::create([
            'email'    => strtolower(trim($email)),
            'name'     => $name,
            'password' => $password,
            'role'     => $role,
        ]);
    }

    public static function all(): array
    {
        return User::select('id', 'email', 'name', 'role')->get()->toArray();
    }
}