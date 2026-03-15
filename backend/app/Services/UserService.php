<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    public function createUser(string $email, string $password)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email");
        }

        if (UserRepository::findByEmail($email)) {
            throw new \Exception("User already exists");
        }

        return UserRepository::create($email, $password);
    }

    public function getUserById(int $id)
    {
        $user = UserRepository::findById($id);
        if (!$user) {
            throw new \Exception("User not found");
        }

        return $user;
    }

    public function getAllUsers()
    {
        return UserRepository::all();
    }
}