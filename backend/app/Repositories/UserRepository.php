<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public static function findByEmail($email)
    {
        return User::select('id', 'email', 'password', 'role')->where('email', $email)->first();
    }

    public static function findById($id)
    {
        return User::select('id', 'email', 'role')->find($id);
    }

    public static function create($email, $password, $role = 'user')
    {
        return User::create([
            "email" => $email,
            "password" => $password,
            "role" => $role
        ]);
    }

    public static function all()
    {
        return User::select('id', 'email', 'role')->get();
    }
}