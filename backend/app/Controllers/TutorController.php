<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
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
        $page = max(1, Request::query('page', 1));
        $perPage = min(100, max(1, Request::query('per_page', 20)));
        $search = Request::query('search', null);

        $result = TutorRepository::paginate([
            'search' => $search,
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
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $name         = Validators::stringCheck($data['name'] ?? '', 'Title', 100);
            $contactEmail = Validators::email($data['contact_email'] ?? null);
            $bio          = isset($data['bio']) ? Validators::stringCheck($data['bio'], 'Content', 1000) : null;
            $rate         = Validators::rangeCheck($data['rate'] ?? null, 0, 1000, 'Rate');
            $moduleCodes  = array_filter(
                array_map('strtoupper', (array) ($data['module_codes'] ?? [])),
                fn($c) => preg_match('/^[A-Za-z0-9]{2,10}$/', $c)
            );

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
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
