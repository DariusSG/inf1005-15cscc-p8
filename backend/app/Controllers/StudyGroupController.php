<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
use App\Core\Logger;
use App\Middleware\JwtMiddleware;
use App\Repositories\StudyGroupRepository;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Study Groups',
    description: 'Study group operations'
)]
class StudyGroupController
{
    #[OA\Get(
        path: "/study-groups",
        summary: "List study groups (paginated)",
        tags: ["Study Groups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", schema: new OA\Schema(type: "string"), description: "Search in name or module code"),
            new OA\Parameter(
                parameter: "QueryPage",
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, default: 1)
            ),
            new OA\Parameter(
                parameter: "QueryPerPage",
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 20)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/StudyGroup")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $page = max(1, Request::query('page', 1));
        $perPage = min(100, max(1, Request::query('per_page', 20)));
        $search = Request::query('search', null);

        $result = StudyGroupRepository::paginate([
            'search' => $search,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    #[OA\Post(
        path: "/study-groups",
        summary: "Create a new study group",
        tags: ["Study Groups"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "module_code"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "module_code", type: "string"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "meeting_time", type: "string", nullable: true),
                    new OA\Property(property: "location", type: "string", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: "Study group created", 
                content: new OA\JsonContent(ref: "#/components/schemas/StudyGroup")
            ),
            new OA\Response(
                response: 400, 
                description: "Validation error", 
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $name        = Validators::stringCheck($data['name'] ?? '', 'Title', 150);
            // Accept only snake_case 'module_code' from frontend
            $moduleCode  = Validators::moduleCode($data['module_code'] ?? null);
            $description = isset($data['description'])  ? Validators::stringCheck($data['description'],'Content', 500) : null;
            $location    = isset($data['location'])      ? Validators::stringCheck($data['location'], 'Content', 150)  : null;
            $meetingTime = isset($data['meeting_time'])  ? Validators::stringCheck($data['meeting_time'], 'Content', 100)  : null;

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
            Logger::channel()->error('Error at StudyGroupController@store', ['exception' => $e]);
            Response::json(['error' => 'Invalid study group data'], 400);
        }
    }
}
