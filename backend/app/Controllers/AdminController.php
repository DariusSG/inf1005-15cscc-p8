<?php

namespace App\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
use App\Repositories\ModuleRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Services\AuditService;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;


#[OA\SecurityScheme(
    securityScheme: "bearerAuth", type: "http",
    scheme: "bearer", bearerFormat: "JWT",
    description: "Enter your JWT access token"
)]
#[OA\Tag(
    name: 'Admin',
    description: 'Administrative operations (admin role required)'
)]
#[OA\Schema(
    schema: "ErrorResponse",
    properties: [
        new OA\Property(property: "error", type: "string", example: "Invalid credentials")
    ]
)]
#[OA\Response(
    response: "Forbidden",
    description: "Access denied",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
#[OA\Response(
    response: "NotFound",
    description: "Resource not found",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', description: 'Total number of items', example: 150),
        new OA\Property(property: 'per_page', type: 'integer', description: 'Items per page', example: 20),
        new OA\Property(property: 'current_page', type: 'integer', description: 'Current page number', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', description: 'Last page number', example: 8)
    ]
)]
readonly class AdminController
{
    public function __construct(
        protected UserService $userService
    ) {}

    #[OA\Get(
        path: "/admin/reviews/report",
        summary: "List all reported reviews (paginated)",
        tags: ["Admin"], security: [["bearerAuth" => []]], 
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Paginated list of reported reviews",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/ReportedReviews"),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ])
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function reportedReviews_show(): void
    {
        $page    = max(1, Request::query('page', 1));
        $perPage = min(100, max(1, Request::query('per_page', 20)));

        $result = ReviewRepository::reportedReviewsPaginated($perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }


    #[OA\Get(
        path: "/admin/users",
        summary: "List all users (paginated)",
        tags: ["Admin"], security: [["bearerAuth" => []]],
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Paginated list of users",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/User")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )    
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function user_index(): void
    {
        $page    = max(1, Request::query('page', 1));
        $perPage = min(100, max(1, Request::query('per_page', 20)));

        $result  = UserRepository::paginate($page, $perPage);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    #[OA\Get(
        path: "/admin/users/{id}",
        summary: "Get user by ID",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", description: "User ID")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User details",
                content: new OA\JsonContent(ref: "#/components/schemas/User")
            ),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function user_show(int $id): void
    {
        try {
            $user = $this->userService->getUserById($id);
            Response::json($user);
        } catch (Exception $e) {
            Logger::channel()->error('Error at AdminController@user_show', ['exception' => $e]);
            Response::json(["error" => $e->getMessage()], 404);
        }
    }

    
    #[OA\Post(
        path: "/admin/modules",
        summary: "Create a new module",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Module")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Module created",
                content: new OA\JsonContent(ref: "#/components/schemas/Module")
            ),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 409, description: "Module already exists")
        ]
    )]
    public function module_store(): void
    {
        $data = Request::body() ?? [];

        try {
            // 1. Basic Validation
            $code = Validators::moduleCode($data['code'] ?? null);
            if (!$code) {
                throw new InvalidArgumentException('Valid module code is required');
            }

            if (ModuleRepository::findByCode($code)) {
                Response::json(['error' => 'Module already exists'], 409);
                return;
            }

            $name        = Validators::stringCheck($data['name'] ?? '', 'Title', 150);
            $description = !empty(trim((string)($data['description'] ?? '')))
                ? Validators::stringCheck($data['description'], 'Content', 3000)
                : null;

            // 2. Credits & Semester Logic
            $credits = filter_var($data['credits'] ?? 4, FILTER_VALIDATE_INT, [
                "options" => ["min_range" => 1, "max_range" => 20]
            ]);
            if ($credits === false) {
                throw new InvalidArgumentException('Credits must be an integer between 1 and 20');
            }

            $semesters = array_map(fn($s) => (int)$s, (array)($data['semesters'] ?? []))
                    |> (fn($x) => array_filter($x, fn($s) => $s >= 1 && $s <= 3))
                    |> array_unique(...)
                    |> array_values(...);

            // 3. Prerequisite Logic (Optimized)
            $prereqs = array_map(fn($p) => Validators::moduleCode(is_string($p) ? $p : null), (array)($data['prereqs'] ?? []))
                    |> array_filter(...)
                    |> array_unique(...)
                    |> array_values(...);

            if (in_array($code, $prereqs, true)) {
                throw new InvalidArgumentException('Module cannot list itself as a prerequisite');
            }

            // Batch check prerequisites to avoid N+1 queries
            if (!empty($prereqs)) {
                $existingPrereqs = ModuleRepository::findManyByCodes($prereqs);
                $existingCodes   = $existingPrereqs->pluck('code')->toArray();
                $missing         = array_diff($prereqs, $existingCodes);

                if (!empty($missing)) {
                    Response::json([
                        'error' => 'Some prerequisite modules do not exist',
                        'missing_prereqs' => array_values($missing),
                    ], 400);
                }
            }

            // 4. Persistence
            $module = ModuleRepository::create([
                'code'        => $code,
                'name'        => $name,
                'description' => $description,
                'faculty'     => $data['faculty'] ?? null,
                'credits'     => $credits,
                'semesters'   => $semesters,
                'prereqs'     => $prereqs,
            ]);

            // 5. Audit Logging
            AuditService::log(AuditService::EVENT_ADMIN_ACTION, [
                'action' => 'create_module',
                'module_code' => $code
            ]);

            Response::json(ModuleRepository::format($module), 201);

        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Logger::channel()->error('Error at AdminController@module_store', ['exception' => $e]);
            Response::json(['error' => 'An unexpected error occurred'], 500);
        }
    }
}
