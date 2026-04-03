<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
use App\Core\Logger;
use App\Middleware\JwtMiddleware;
use App\Repositories\TutorRepository;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Tutors',
    description: 'Tutor listings and management'
)]
class TutorController
{
    #[OA\Get(
        path: "/tutors",
        summary: "List tutors (paginated)",
        tags: ["Tutors"],
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
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Tutor")),
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

        $result = TutorRepository::paginate([
            'module_code' => $moduleCode,
            'search' => $search,
            'author_id' => $author_id,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    #[OA\Post(
        path: "/tutors",
        summary: "Create a new tutor listing",
        tags: ["Tutors"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "contact_email"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "module_codes", type: "array", items: new OA\Items(type: "string")),
                    new OA\Property(property: "contact_email", type: "string", format: "email"),
                    new OA\Property(property: "bio", type: "string"),
                    new OA\Property(property: "rate", type: "number", minimum: 0, maximum: 1000)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: "Tutor listing created", 
                content: new OA\JsonContent(ref: "#/components/schemas/Tutor")
            ),
            new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    #[OA\Put(
        path: "/tutors/{id}",
        summary: "Update a tutor listing",
        tags: ["Tutors"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "module_codes", type: "array", items: new OA\Items(type: "string")),
                    new OA\Property(property: "contact_email", type: "string", format: "email"),
                    new OA\Property(property: "bio", type: "string"),
                    new OA\Property(property: "rate", type: "number", minimum: 0, maximum: 1000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated", content: new OA\JsonContent(ref: "#/components/schemas/Tutor")),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(int $id): void
    {
        $userId = JwtMiddleware::userId();
        $tutor  = TutorRepository::find($id);
        if (!$tutor) {
            Response::json(['error' => 'Not found'], 404);
        }
        if ($tutor->user_id !== $userId) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = Request::body();
        try {
            $updates = [];
            if (isset($data['bio']))           $updates['bio']           = Validators::stringCheck($data['bio'], 'Content', 1000);
            if (isset($data['rate']))          $updates['rate']          = Validators::rangeCheck($data['rate'], 0, 1000, 'Rate');
            if (isset($data['contact_email'])) $updates['contact_email'] = Validators::email($data['contact_email']);

            $moduleCodes = null;
            if (isset($data['module_codes'])) {
                $moduleCodes = array_values(array_filter(
                    array_map('strtoupper', (array) $data['module_codes']),
                    fn($c) => preg_match('/^[A-Za-z0-9]{2,10}$/', $c)
                ));
            }

            $updated = TutorRepository::update($id, $updates, $moduleCodes);
            Response::json(TutorRepository::format($updated));
        } catch (\InvalidArgumentException $e) {
            Logger::channel()->error('Error at TutorController@update', ['exception' => $e]);
            Response::json(['error' => 'Invalid tutor data'], 400);
        }
    }

    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $requiredFields = ['name', 'contact_email', 'bio', 'rate'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $data)) {
                    Response::json(['message' => "{$field} is required", 'code' => 'API_ERROR'], 400);
                }
            }

            $name         = Validators::stringCheck($data['name'], 'Title', 100);
            $contactEmail = Validators::email($data['contact_email']);
            $bio          = Validators::stringCheck($data['bio'], 'Content', 1000);
            $rate         = Validators::rangeCheck($data['rate'], 0, 1000, 'Rate');

            $rawModuleCodes = array_key_exists('module_codes', $data) ? $data['module_codes'] : [];
            if (!is_array($rawModuleCodes)) {
                throw new \InvalidArgumentException('module_codes must be an array');
            }

            $moduleCodes = array_values(array_unique(array_map(function ($code) {
                return Validators::moduleCode(is_string($code) ? $code : null);
            }, $rawModuleCodes)));

            if (in_array(null, $moduleCodes, true)) {
                throw new \InvalidArgumentException('module_codes contains invalid module code');
            }

            $tutor = TutorRepository::create([
                'user_id'       => $userId,
                'name'          => $name,
                'contact_email' => $contactEmail,
                'bio'           => $bio,
                'rate'          => $rate,
                'module_codes'  => array_values($moduleCodes),
            ]);

            Response::json(TutorRepository::format($tutor), 201);
        } catch (\InvalidArgumentException $e) {
            Logger::channel()->error('Error at TutorController@store', ['exception' => $e]);
            Response::json(['message' => 'Invalid tutor data', 'code' => 'API_ERROR'], 400);
        }
    }

    #[OA\Delete(
        path: "/tutors/{id}",
        summary: "Delete a tutor listing",
        tags: ["Tutors"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", minimum: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Tutor listing deleted"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Tutor listing not found")
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

        $tutor = TutorRepository::find($id);
        if (!$tutor) {
            Response::json(['message' => 'Tutor listing not found', 'code' => 'API_ERROR'], 404);
        }

        if ($tutor->user_id !== $userId && $role !== 'admin') {
            Response::json(['message' => 'Forbidden', 'code' => 'API_ERROR'], 403);
        }

        TutorRepository::delete($id);
        Response::json(['message' => 'Tutor listing deleted']);
    }
}
