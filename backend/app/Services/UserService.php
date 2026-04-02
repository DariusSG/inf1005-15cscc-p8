<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudyGroup;
use App\Models\Tutor;
use App\Models\HelpRequest;
use App\Models\Review;
use App\Repositories\UserRepository;
use InvalidArgumentException;

class UserService
{
    public function __construct(
        protected readonly TokenService $tokenService,
    ) {}

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

        if ($user->isDeleted()) {
            throw new InvalidArgumentException("User is deleted");
        }

        return $user;
    }

    public function deleteUser(int $id): void
    {
        $user = UserRepository::findByIdWithTrashed($id);
        if (!$user) {
            throw new InvalidArgumentException("User not found");
        }

        if ($user->isDeleted()) {
            return;
        }

        // Delete study groups owned by user
        StudyGroup::where('user_id', $id)->delete();

        // Delete tutors owned by user
        Tutor::where('user_id', $id)->delete();

        // Delete help requests owned by user
        HelpRequest::where('user_id', $id)->delete();

        // Soft-delete reviews authored by user (will show as user_id -1)
        Review::where('user_id', $id)->delete();

        // Delete the user and revoke all tokens
        UserRepository::delete($user);
        $this->tokenService->revokeAllTokensForUser($id);
    }
}
