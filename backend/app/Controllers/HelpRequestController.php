<?php

namespace App\Controllers;

use App\Core\Validators;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\HelpRequestRepository;
use InvalidArgumentException;

use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Help Requests',
    description: 'Academic help request system'
)]
class HelpRequestController
{
    #[OA\Get(
        path: "/help-requests",
        summary: "List help requests (paginated)",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", schema: new OA\Schema(type: "string")),
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
                description: "Paginated list",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/HelpRequest")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )
            )
        ]
    )]
    public function index($search, $page = 1, $perPage = 20)
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $search = $search ?? null;

        $result = HelpRequestRepository::paginate([
            'search' => $search,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    #[OA\Get(
        path: "/help-requests/{id}",
        summary: "Get a single help request",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The unique ID of the help request",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Help request details",
                content: new OA\JsonContent(ref: "#/components/schemas/HelpRequest")
            ),
            new OA\Response(
                response: 404, 
                description: "Help request not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function show(int $id)
    {
        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['error' => 'Not found'], 404);
        }
        Response::json(HelpRequestRepository::format($req));
    }

    #[OA\Post(
        path: "/help-requests",
        summary: "Create a new help request",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ["title", "contact_email"],
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "module_code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "urgency", type: "string", enum: ["low", "medium", "high"]),
                    new OA\Property(property: "contact_email", type: "string", format: "email"),
                    new OA\Property(property: "has_bounty", type: "boolean"),
                    new OA\Property(property: "bounty_amount", type: "number", minimum: 0)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created", content: new OA\JsonContent(ref: "#/components/schemas/HelpRequest")),
            new OA\Response(response: 400, ref: "#/components/responses/ValidationError")
        ]
    )]
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $title        = Validators::stringCheck($data['title'] ?? '', 'Title', 200);
            $moduleCode   = Validators::moduleCode($data['module_code'] ?? null);
            $description  = Validators::stringCheck($data['description'] ?? '', 'Content', 5000);
            $bountyAmount = Validators::rangeCheck($data['bounty_amount'] ?? null, 0, 10000, 'Bounty amount');
            $urgency      = $data['urgency'] ?? 'low';
            if (!in_array($urgency, ['low', 'medium', 'high'], true)) {
                throw new InvalidArgumentException('urgency must be low, medium, or high');
            }

            $contactEmail = Validators::email($data['contact_email'] ?? null);

            $req = HelpRequestRepository::create([
                'user_id'       => $userId,
                'title'         => $title,
                'module_code'   => $moduleCode,
                'description'   => $description,
                'urgency'       => $urgency,
                'contact_email' => $contactEmail,
                'has_bounty'    => (bool)($data['has_bounty'] ?? false),
                'bounty_amount' => $bountyAmount,
            ]);

            Response::json(HelpRequestRepository::format($req), 201);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/help-requests/{id}/respond",
        summary: "Add a response to a help request",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["content"],
                properties: [
                    new OA\Property(property: "content", type: "string", example: "I can help you with this module!")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Response added successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/HelpRequestResponse")
            ),
            new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Help request not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function respond(int $id)
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        $content = trim($data['content'] ?? '');
        if (!$content) {
            Response::json(['error' => 'content is required'], 400);
        }

        if (!HelpRequestRepository::find($id)) {
            Response::json(['error' => 'Help request not found'], 404);
        }

        $response = HelpRequestRepository::addResponse($id, $userId, $content);
        Response::json($response->load('author:id,email')->toArray(), 201);
    }

    #[OA\Post(
        path: "/help-requests/{id}/solve",
        summary: "Mark a help request as solved",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Help request marked as solved",
                content: new OA\JsonContent(ref: "#/components/schemas/HelpRequest")
            ),
            new OA\Response(
                response: 403, 
                description: "Forbidden - Not the owner or an admin",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404, 
                description: "Help request not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function solve(int $id)
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();

        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['error' => 'Help request not found'], 404);
        }

        // Only the owner or an admin can mark as solved
        if ($req->user_id !== $userId && $role !== 'admin') {
            Response::json(['error' => 'Forbidden'], 403);
        }

        Response::json(HelpRequestRepository::format(HelpRequestRepository::markSolved($id)));
    }
}
