<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? null;

        $result = StudyGroupRepository::paginate([
            'search' => $search,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
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

        try {
            $name        = Validators::title($data['name'] ?? '', 150);
            $moduleCode  = Validators::moduleCode($data['module_code'] ?? null);
            $description = isset($data['description'])  ? Validators::text($data['description'],  1000) : null;
            $location    = isset($data['location'])      ? Validators::text($data['location'],      200)  : null;
            $meetingTime = isset($data['meeting_time'])  ? Validators::text($data['meeting_time'],  100)  : null;

            $group = StudyGroupRepository::create([
                'user_id'      => $userId,
                'name'         => $name,
                'module_code'  => $moduleCode,
                'description'  => $description,
                'meeting_time' => $meetingTime,
                'location'     => $location,
            ]);

            Response::json($group->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}