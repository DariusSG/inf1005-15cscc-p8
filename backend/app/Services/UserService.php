<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use InvalidArgumentException;

class UserService
{
    /**
     * @throws InvalidArgumentException
     */
    public function createUser(string $name, string $email, string $hashed, string $role = "student"): User
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email");
        }

        if (UserRepository::findByEmail($email)) {
            throw new InvalidArgumentException('An account for this email already exists.');
        }

        if (strlen(trim($name)) < 2) {
            throw new InvalidArgumentException('Name must be at least 2 characters.');
        }

        return UserRepository::create($email, $hashed, $role, trim($name));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getUserById(int $id): User
    {
        $user = UserRepository::findById($id);
        if (!$user) {
            throw new InvalidArgumentException("User not found");
        }

        return $user;
    }
}