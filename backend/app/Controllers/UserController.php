<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Container;


/**
 * @OA\Tag(
 *     name="Users",
 *     description="Operations related to users"
 * )
 */
class UserController
{
    protected $userService;

    public function __construct()
    {
        $this->userService = Container::resolve('UserService');
    }

    /**
     * @OA\Get(
     *   path="/users",
     *   summary="Get list of users",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of users"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $users = $this->userService->getAllUsers();
        Response::json($users);
    }

    /**
     * @OA\Get(
     *   path="/users/{id}",
     *   summary="Get a user by ID",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="User found"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="User not found")
     * )
     */
    public function show($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            Response::json($user);
        } catch (\Exception $e) {
            Response::json(["error"=>$e->getMessage()],404);
        }
    }
}