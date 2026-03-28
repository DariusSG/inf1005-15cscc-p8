<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
use App\Middleware\JwtMiddleware;
use App\Repositories\ModuleRepository;
use App\Repositories\ReviewRepository;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Modules',
    description: 'Module information and management'
)]
class ModuleController
{
    #[OA\Get(
        path: "/modules",
        summary: "List all modules (paginated)",
        tags: ["Modules"],
        security: [["bearerAuth" => []]],
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
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Module")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )
            )
        ]
    )]
    public function index(int $page = 1, int $perPage = 20)
    {
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $result  = ModuleRepository::paginate($page, $perPage);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    #[OA\Get(
        path: "/modules/{code}",
        summary: "Get module with reviews",
        tags: ["Modules"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "code", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Module details with reviews",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/Module"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "reviews", 
                                    type: "array", 
                                    items: new OA\Items(ref: "#/components/schemas/Review")
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not Found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function show(string $code)
    {
        $module = ModuleRepository::findByCode(strtoupper($code));
        if (!$module) {
            Response::json(['error' => 'Module not found'], 404);
        }

        $userId  = JwtMiddleware::userId() ?? 0;
        $reviews = ReviewRepository::forModule($module->code, $userId);

        Response::json(array_merge(
            ModuleRepository::format($module),
            ['reviews' => $reviews]
        ));
    }
}
