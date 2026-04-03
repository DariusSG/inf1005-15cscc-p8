<?php

namespace App\Controllers;

use App\Core\Validators;
use App\Core\Request;
use App\Core\Response;
use App\Core\Logger;
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
    public function index()
    {
        try {
            $page = max(1, Request::query('page', 1));
            $perPage = min(100, max(1, Request::query('per_page', 20)));
            $moduleCode = Validators::moduleCode(Request::query('module_code', null));
            $search = Validators::search(Request::query('search', null));
            $author_id = Validators::optionalPositiveInt(Request::query('user_id', null), 'user_id');
        } catch (InvalidArgumentException $e) {
            Response::json(['message' => 'Invalid query parameters', 'code' => 'API_ERROR'], 400);
        }

        $result = HelpRequestRepository::paginate([
            'module_code' => $moduleCode,
            'search' => $search,
            'author_id' => $author_id,
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
        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['message' => 'Not found', 'code' => 'API_ERROR'], 404);
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
    #[OA\Put(
        path: "/help-requests/{id}",
        summary: "Update a help request",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "urgency", type: "string", enum: ["low", "medium", "high"]),
                    new OA\Property(property: "contact_email", type: "string", format: "email"),
                    new OA\Property(property: "has_bounty", type: "boolean"),
                    new OA\Property(property: "bounty_amount", type: "number", minimum: 0)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated", content: new OA\JsonContent(ref: "#/components/schemas/HelpRequest")),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(int $id): void
    {
        $userId = JwtMiddleware::userId();
        $req    = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['error' => 'Not found'], 404);
        }
        if ($req->user_id !== $userId) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = Request::body();
        try {
            $updates = [];
            if (isset($data['title']))        $updates['title']        = Validators::stringCheck($data['title'], 'Title', 200);
            if (isset($data['description']))  $updates['description']  = Validators::stringCheck($data['description'], 'Content', 5000);
            if (isset($data['contact_email'])) $updates['contact_email'] = Validators::email($data['contact_email']);
            if (isset($data['urgency'])) {
                if (!in_array($data['urgency'], ['low', 'medium', 'high'], true)) {
                    throw new InvalidArgumentException('urgency must be low, medium, or high');
                }
                $updates['urgency'] = $data['urgency'];
            }
            if (isset($data['has_bounty']))   $updates['has_bounty']   = (bool)$data['has_bounty'];
            if (isset($data['bounty_amount'])) $updates['bounty_amount'] = Validators::rangeCheck($data['bounty_amount'], 0, 10000, 'Bounty amount');

            $updated = HelpRequestRepository::update($id, $updates);
            Response::json(HelpRequestRepository::format($updated));
        } catch (InvalidArgumentException $e) {
            Logger::channel()->error('Error at HelpRequestController@update', ['exception' => $e]);
            Response::json(['error' => 'Invalid request data'], 400);
        }
    }

    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $requiredFields = ['title', 'module_code', 'description', 'contact_email', 'urgency', 'has_bounty'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $data)) {
                    Response::json(['message' => "{$field} is required", 'code' => 'API_ERROR'], 400);
                }
            }

            $title        = Validators::stringCheck($data['title'], 'Title', 200);
            $moduleCode   = Validators::moduleCode($data['module_code']);
            $description  = Validators::stringCheck($data['description'], 'Content', 5000);

            if ($data['has_bounty']) {
                if (!array_key_exists('bounty_amount', $data)) {
                    Response::json(['message' => "bounty_amount is required when has_bounty is true", 'code' => 'API_ERROR'], 400);
                }
                $bountyAmount = Validators::rangeCheck($data['bounty_amount'], 0, 10000, 'Bounty amount');
            } else {
                $bountyAmount = null;
            }
            
            $urgency      = $data['urgency'];
            if (!in_array($urgency, ['low', 'medium', 'high'], true)) {
                throw new InvalidArgumentException('urgency must be low, medium, or high');
            }

            $contactEmail = Validators::email($data['contact_email']);

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
            Logger::channel()->error('Error at HelpRequestController@store', ['exception' => $e]);
            Response::json(['message' => 'Invalid request', 'code' => 'API_ERROR'], 400);
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

        try {
            $id = Validators::positiveInt($id, 'id');
            $content = Validators::stringCheck($data['content'] ?? '', 'content', 5000);
        } catch (InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        if (!HelpRequestRepository::find($id)) {
            Response::json(['message' => 'Help request not found', 'code' => 'API_ERROR'], 404);
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

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['message' => 'Help request not found', 'code' => 'API_ERROR'], 404);
        }

        // Only the owner or an admin can mark as solved
        if ($req->user_id !== $userId && $role !== 'admin') {
            Response::json(['message' => 'Forbidden', 'code' => 'API_ERROR'], 403);
        }

        Response::json(HelpRequestRepository::format(HelpRequestRepository::markSolved($id)));
    }

    #[OA\Delete(
        path: "/help-requests/{id}",
        summary: "Delete a help request",
        tags: ["Help Requests"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", minimum: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Help request deleted"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Help request not found")
        ]
    )]
    public function delete(int $id): void
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['message' => 'Help request not found', 'code' => 'API_ERROR'], 404);
        }

        if ($req->user_id !== $userId && $role !== 'admin') {
            Response::json(['message' => 'Forbidden', 'code' => 'API_ERROR'], 403);
        }

        HelpRequestRepository::delete($id);
        Response::json(['message' => 'Help request deleted']);
    }
}
