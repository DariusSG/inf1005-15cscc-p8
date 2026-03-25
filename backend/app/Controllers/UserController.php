<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Container;
use App\Repositories\BaseRepository;
use App\Repositories\UserRepository;

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
     *   summary="Get paginated list of users",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page",     in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Paginated list of users"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $page    = max(1, (int) ($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));

        $total = \App\Models\User::count();
        $users = \App\Models\User::select('id', 'email', 'name', 'role')
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        Response::json([
            'data' => $users,
            'meta' => BaseRepository::buildPaginationMeta($total, $perPage, $page),
        ]);
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