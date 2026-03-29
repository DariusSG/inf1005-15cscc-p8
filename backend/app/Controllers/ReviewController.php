<?php

namespace App\Controllers;

use App\Core\Validators;
use App\Core\Request;
use App\Core\Response;
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
            ),
            new OA\Parameter(name: "module_code", in: "query", schema: new OA\Schema(type: "string")),
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
    public function index($search, $moduleCode, int $page = 1, int $perPage = 20)
    {
        $userId = JwtMiddleware::userId() ?? 0;
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $moduleCode = $moduleCode ?? null;
        $search = Validators::search($search ?? null);

        $result = ReviewRepository::paginate([
            'user_id' => $userId,
            'module_code' => $moduleCode,
            'search' => $search,
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
            Response::json(['error' => 'Admins cannot write reviews'], 403);
        }

        try {
            $moduleCode = Validators::moduleCode($data['module_code'] ?? '');
            if (!ModuleRepository::findByCode($moduleCode)) {
                Response::json(['error' => 'Module not found'], 404);
            }

            $rating = Validators::rangeCheck($data['rating'] ?? 0, 1, 5, 'Rating');
            $title = Validators::stringCheck($data['title'] ?? '', 'Title', 200);
            $content = Validators::stringCheck($data['content'] ?? '', 'Content', 10000);

            $workload = isset($data['workload']) ? Validators::stringCheck($data['workload'], 'Content', 50) : null;
            $difficulty = isset($data['difficulty']) ? Validators::stringCheck($data['difficulty'], 'Content', 50) : null;
            $usefulness = isset($data['usefulness']) ? Validators::stringCheck($data['usefulness'], 'Content', 50) : null;

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
            Response::json(['error' => $e->getMessage()], 400);
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

        $review = ReviewRepository::find($id);
        if (!$review) {
            Response::json(['error' => 'Review not found'], 404);
        }

        // Only the author or an admin may edit
        if ($review->user_id !== $userId && $role !== 'admin') {
            Response::json(['error' => 'Forbidden'], 403);
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
            Response::json(['error' => $e->getMessage()], 400);
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
            Response::json(['error' => 'Admins cannot vote'], 403);
        }

        $type = $data['type'] ?? '';
        if (!in_array($type, ['up', 'down'])) {
            Response::json(['error' => 'type must be "up" or "down"'], 400);
        }

        if (!ReviewRepository::find($id)) {
            Response::json(['error' => 'Review not found'], 404);
        }

        $review = ReviewRepository::toggleVote($id, $userId, $type);
        Response::json($review);
    }

    #[OA\Post(
        path: "/reviews/{id}/report",
        summary: "Toggle report on a review",
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

        if ($role === 'admin') {
            Response::json(['error' => 'Admins cannot report reviews'], 403);
        }

        if (!ReviewRepository::find($id)) {
            Response::json(['error' => 'Review not found'], 404);
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

        $text = trim($data['text'] ?? '');
        if (!$text) {
            Response::json(['error' => 'text is required'], 400);
        }

        if (!ReviewRepository::find($id)) {
            Response::json(['error' => 'Review not found'], 404);
        }

        $comment = ReviewRepository::addComment($id, $userId, $text);
        Response::json($comment->load('author:id,email')->toArray(), 201);
    }
}
