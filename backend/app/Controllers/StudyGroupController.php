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
        parameters: [
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
            ),
            new OA\Parameter(name: "module_code", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "user_id", in: "query", schema: new OA\Schema(type: "integer", minimum: 1)),
            new OA\Parameter(name: "search", in: "query", schema: new OA\Schema(type: "string"))
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
        try {
            $page = max(1, Request::query('page', 1));
            $perPage = min(100, max(1, Request::query('per_page', 20)));
            $moduleCode = Validators::moduleCode(Request::query('module_code', null));
            $search = Validators::search(Request::query('search', null));
            $author_id = Validators::optionalPositiveInt(Request::query('user_id', null), 'user_id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => 'Invalid query parameters', 'code' => 'API_ERROR'], 400);
        }

        $currentUserId = JwtMiddleware::userId();

        $result = StudyGroupRepository::paginate([
            'module_code' => $moduleCode,
            'search' => $search,
            'author_id' => $author_id,
            'current_user_id' => $currentUserId,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    #[OA\Put(
        path: "/study-groups/{id}",
        summary: "Update a study group",
        tags: ["Study Groups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "meeting_time", type: "string", nullable: true),
                    new OA\Property(property: "location", type: "string", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated", content: new OA\JsonContent(ref: "#/components/schemas/StudyGroup")),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(int $id): void
    {
        $userId = JwtMiddleware::userId();
        $group  = StudyGroupRepository::find($id);
        if (!$group) {
            Response::json(['error' => 'Not found'], 404);
        }
        if ($group->user_id !== $userId) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = Request::body();
        try {
            $updates = [];
            if (isset($data['name']))         $updates['name']         = Validators::stringCheck($data['name'], 'Title', 150);
            if (isset($data['description']))  $updates['description']  = Validators::stringCheck($data['description'], 'Content', 500);
            if (isset($data['location']))     $updates['location']     = Validators::stringCheck($data['location'], 'Content', 150);
            if (isset($data['meeting_time'])) $updates['meeting_time'] = Validators::stringCheck($data['meeting_time'], 'Content', 100);

            $updated = StudyGroupRepository::update($id, $updates);
            Response::json(StudyGroupRepository::format($updated));
        } catch (\InvalidArgumentException $e) {
            Logger::channel()->error('Error at StudyGroupController@update', ['exception' => $e]);
            Response::json(['error' => 'Invalid study group data'], 400);
        }
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
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "meeting_time", type: "string"),
                    new OA\Property(property: "location", type: "string")
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
            $requiredFields = ['name', 'module_code', 'description', 'location', 'meeting_time'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $data)) {
                    Response::json(['message' => "{$field} is required", 'code' => 'API_ERROR'], 400);
                }
            }

            $name        = Validators::stringCheck($data['name'], 'Title', 150);
            $moduleCode  = Validators::moduleCode($data['module_code']);
            $description = Validators::stringCheck($data['description'], 'Content', 500);
            $location    = Validators::stringCheck($data['location'], 'Content', 150);
            $meetingTime = Validators::stringCheck($data['meeting_time'], 'Content', 100);

            $group = StudyGroupRepository::create([
                'user_id'      => $userId,
                'name'         => $name,
                'module_code'  => $moduleCode,
                'description'  => $description,
                'meeting_time' => $meetingTime,
                'location'     => $location,
            ]);

            Response::json(StudyGroupRepository::format($group), 201);
        } catch (\InvalidArgumentException $e) {
            Logger::channel()->error('Error at StudyGroupController@store', ['exception' => $e]);
            Response::json(['message' => 'Invalid study group data', 'code' => 'API_ERROR'], 400);
        }
    }

    #[OA\Post(
        path: "/study-groups/{id}/join",
        summary: "Join a study group",
        tags: ["Study Groups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Join operation result"),
            new OA\Response(response: 404, description: "Study group not found")
        ]
    )]
    public function join(int $id): void
    {
        $userId = JwtMiddleware::userId();

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $result = StudyGroupRepository::join($id, (int) $userId);

        if (!$result['group']) {
            Response::json(['message' => 'Study group not found', 'code' => 'API_ERROR'], 404);
        }

        Response::json([
            'joined' => $result['joined'],
            'group' => StudyGroupRepository::format(StudyGroupRepository::find($id), $userId),
        ]);
    }

    #[OA\Post(
        path: "/study-groups/{id}/leave",
        summary: "Leave a study group",
        tags: ["Study Groups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Leave operation result"),
            new OA\Response(response: 404, description: "Study group not found")
        ]
    )]
    public function leave(int $id): void
    {
        $userId = JwtMiddleware::userId();

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $result = StudyGroupRepository::leave($id, (int) $userId);

        if (!$result['group']) {
            Response::json(['message' => 'Study group not found', 'code' => 'API_ERROR'], 404);
        }

        Response::json([
            'left' => $result['left'],
            'group' => StudyGroupRepository::format(StudyGroupRepository::find($id), $userId),
        ]);
    }

    #[OA\Delete(
        path: "/study-groups/{id}",
        summary: "Delete a study group",
        tags: ["Study Groups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", minimum: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Study group deleted"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Study group not found")
        ]
    )]
    public function delete(int $id): void
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $group = StudyGroupRepository::find($id);
        if (!$group) {
            Response::json(['message' => 'Study group not found', 'code' => 'API_ERROR'], 404);
        }

        if ($group->user_id !== $userId && $role !== 'admin') {
            Response::json(['message' => 'Forbidden', 'code' => 'API_ERROR'], 403);
        }

        StudyGroupRepository::delete($id);
        Response::json(['message' => 'Study group deleted']);
    }
}
