<?php

namespace App\Controllers;

use App\Core\Validators;
use App\Core\Request;
use App\Core\Response;
use App\Core\Logger;
use App\Middleware\JwtMiddleware;
use App\Repositories\ReviewRepository;
use App\Repositories\ModuleRepository;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Reviews',
    description: 'Module review operations'
)]
#[OA\Response(
    response: "ValidationError",
    description: "The provided data was invalid.",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
class ReviewController
{
    #[OA\Get(
        path: "/reviews",
        summary: "List reviews (paginated, filtered)",
        tags: ["Reviews"],
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
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Review")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $userId = JwtMiddleware::userId() ?? 0;

        try {
            $page = max(1, Request::query('page', 1));
            $perPage = min(100, max(1, Request::query('per_page', 20)));
            $moduleCode = Validators::moduleCode(Request::query('module_code', null));
            $search = Validators::search(Request::query('search', null));
            $author_id = Validators::optionalPositiveInt(Request::query('user_id', null), 'user_id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => 'Invalid query parameters', 'code' => 'API_ERROR'], 400);
        }

        $result = ReviewRepository::paginate([
            'user_id' => $userId,
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
        path: "/reviews",
        summary: "Create a new review",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["module_code", "rating", "title", "content"],
                properties: [
                    new OA\Property(property: "module_code", type: "string"),
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5),
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "content", type: "string"),
                    new OA\Property(property: "workload", type: "string"),
                    new OA\Property(property: "difficulty", type: "string"),
                    new OA\Property(property: "usefulness", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created", content: new OA\JsonContent(ref: "#/components/schemas/Review")),
            new OA\Response(response: 403, description: "Admins cannot write reviews", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();
        $data   = Request::body();

        if ($role === 'admin') {
            Response::json(['message' => 'Admins cannot write reviews', 'code' => 'API_ERROR'], 403);
        }

        try {
            $requiredFields = ['module_code', 'rating', 'title', 'content', 'workload', 'difficulty', 'usefulness'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $data)) {
                    Response::json(['message' => "{$field} is required", 'code' => 'API_ERROR'], 400);
                }
            }

            $moduleCode = Validators::moduleCode($data['module_code']);

            if (!ModuleRepository::findByCode($moduleCode)) {
                Response::json(['message' => 'Module not found', 'code' => 'API_ERROR'], 404);
            }

            $rating = Validators::rangeCheck($data['rating'], 1, 5, 'Rating');
            $title = Validators::stringCheck($data['title'], 'Title', 200);
            $content = Validators::stringCheck($data['content'], 'Content', 10000);

            $workload = Validators::stringCheck($data['workload'], 'Content', 50);
            $difficulty = Validators::stringCheck($data['difficulty'], 'Content', 50);
            $usefulness = Validators::stringCheck($data['usefulness'], 'Content', 50);

            $review = ReviewRepository::create([
                'module_code' => $moduleCode,
                'user_id'     => $userId,
                'rating'      => $rating,
                'title'       => $title,
                'content'     => $content,
                'workload'    => $workload,
               'difficulty'  => $difficulty,
                'usefulness'  => $usefulness,
            ]);

            Response::json(ReviewRepository::format($review->fresh(['author', 'comments']), $userId), 201);
        } catch (\InvalidArgumentException $e) {
            Logger::channel()->error('Error at ReviewController@store', ['exception' => $e]);
            Response::json(['message' => 'Invalid review data', 'code' => 'API_ERROR'], 400);
        }
    }

    #[OA\Post(
        path: "/reviews/{id}",
        summary: "Edit your own review",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5),
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "content", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated", content: new OA\JsonContent(ref: "#/components/schemas/Review")),
            new OA\Response(response: 403, ref: "#/components/responses/Forbidden"),
            new OA\Response(response: 404, ref: "#/components/responses/NotFound")
        ]
    )]
    public function update(int $id)
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();
        $data   = Request::body();

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $review = ReviewRepository::find($id);
        if (!$review) {
            Response::json(['message' => 'Review not found', 'code' => 'API_ERROR'], 404);
        }

        // Only the author or an admin may edit
        if ($review->user_id !== $userId && $role !== 'admin') {
            Response::json(['message' => 'Forbidden', 'code' => 'API_ERROR'], 403);
        }

        try {
            $rating     = isset($data['rating'])
                ? Validators::rangeCheck($data['rating'], 1, 5, 'Rating')
                : $review->rating;
            $title      = isset($data['title'])
                ? Validators::stringCheck($data['title'], 'Title', 200)
                : $review->title;
            $content    = isset($data['content'])
                ? Validators::stringCheck($data['content'], 'Content', 10000)
                : $review->content;
            $workload   = isset($data['workload'])
                ? Validators::stringCheck($data['workload'], 'Content', 50)
                : $review->workload;
            $difficulty = isset($data['difficulty'])
                ? Validators::stringCheck($data['difficulty'], 'Content', 50)
                : $review->difficulty;
            $usefulness = isset($data['usefulness'])
                ? Validators::stringCheck($data['usefulness'], 'Content', 50)
                : $review->usefulness;
        } catch (\InvalidArgumentException $e) {
            Logger::channel()->error('Error at ReviewController@update', ['exception' => $e]);
            Response::json(['message' => 'Invalid review data', 'code' => 'API_ERROR'], 400);
        }

        $updated = ReviewRepository::update($review, compact(
            'rating', 'title', 'content', 'workload', 'difficulty', 'usefulness'
        ));

        Response::json(ReviewRepository::format($updated->load(['author', 'comments']), $userId));
    }

    #[OA\Post(
        path: "/reviews/{id}/vote",
        summary: "Toggle upvote/downvote",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ["type"], properties: [
                new OA\Property(property: "type", type: "string", enum: ["up", "down"])
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: "Vote toggled"),
            new OA\Response(response: 400, ref: "#/components/responses/ValidationError")
        ]
    )]
    public function vote(int $id)
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();
        $data   = Request::body();

        if ($role === 'admin') {
            Response::json(['message' => 'Admins cannot vote', 'code' => 'API_ERROR'], 403);
        }

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        $type = $data['type'] ?? '';
        if (!in_array($type, ['up', 'down'], true)) {
            Response::json(['message' => 'type must be "up" or "down"', 'code' => 'API_ERROR'], 400);
        }

        if (!ReviewRepository::find($id)) {
            Response::json(['message' => 'Review not found', 'code' => 'API_ERROR'], 404);
        }

        $review = ReviewRepository::toggleVote($id, $userId, $type);
        Response::json($review);
    }

    #[OA\Post(
        path: "/reviews/{id}/report",
        summary: "Report a review",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "reason", type: "string", example: "Inappropriate content")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Report toggled",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "reported", type: "boolean")]
                )
            ),
            new OA\Response(response: 403, description: "Admins cannot report", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, ref: "#/components/responses/NotFound")
        ]
    )]
    public function report(int $id)
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();
        $data   = Request::body();

        try {
            $id = Validators::positiveInt($id, 'id');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        if ($role === 'admin') {
            Response::json(['message' => 'Admins cannot report reviews', 'code' => 'API_ERROR'], 403);
        }

        if (!ReviewRepository::find($id)) {
            Response::json(['message' => 'Review not found', 'code' => 'API_ERROR'], 404);
        }

        $isReported = ReviewRepository::toggleReport($id, $userId, $data['reason'] ?? null);
        Response::json(['reported' => $isReported]);
    }

    #[OA\Post(
        path: "/reviews/{id}/comments",
        summary: "Add a comment to a review",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["text"],
                properties: [
                    new OA\Property(property: "text", type: "string", example: "Great review, thanks!")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Comment added",
                content: new OA\JsonContent(ref: "#/components/schemas/ReviewComment")
            ),
            new OA\Response(response: 400, ref: "#/components/responses/ValidationError"),
            new OA\Response(response: 404, ref: "#/components/responses/NotFound")
        ]
    )]
    public function addComment(int $id)
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $id = Validators::positiveInt($id, 'id');
            $text = Validators::stringCheck($data['text'] ?? '', 'text', 2000);
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage(), 'code' => 'API_ERROR'], 400);
        }

        if (!ReviewRepository::find($id)) {
            Response::json(['message' => 'Review not found', 'code' => 'API_ERROR'], 404);
        }

        $comment = ReviewRepository::addComment($id, $userId, $text);
        Response::json($comment->load('author:id,email')->toArray(), 201);
    }
}
