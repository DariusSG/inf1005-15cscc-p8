<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\StudyGroupRepository;

class StudyGroupController
{
    /**
     * @OA\Get(path="/study-groups", summary="List study groups",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Array of study groups")
     * )
     */
    public function index()
    {
        $search = $_GET['search'] ?? null;
        Response::json(StudyGroupRepository::all($search));
    }

    /**
     * @OA\Post(path="/study-groups", summary="Create study group",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"name"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="module_code", type="string"),
     *       @OA\Property(property="description", type="string"),
     *       @OA\Property(property="meeting_time", type="string"),
     *       @OA\Property(property="location", type="string")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Study group created")
     * )
     */
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        $name = trim($data['name'] ?? '');
        if (!$name) {
            Response::json(['error' => 'name is required'], 400);
        }

        $group = StudyGroupRepository::create([
            'user_id'      => $userId,
            'name'         => $name,
            'module_code'  => isset($data['module_code']) ? strtoupper(trim($data['module_code'])) : null,
            'description'  => $data['description']  ?? null,
            'meeting_time' => $data['meeting_time'] ?? null,
            'location'     => $data['location']     ?? null,
        ]);

        Response::json($group->toArray(), 201);
    }
}