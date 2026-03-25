<?php

namespace App\Controllers;

use App\Core\Validators;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\ReviewRepository;
use App\Repositories\ModuleRepository;

class ReviewController
{
    /**
     * @OA\Get(path="/reviews", summary="List reviews",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="module_code", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Paginated list of reviews")
     * )
     */
    public function index()
    {
        $userId = JwtMiddleware::userId() ?? 0;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $moduleCode = $_GET['module_code'] ?? null;
        $search = Validators::search($_GET['search'] ?? null);

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

    /**
     * @OA\Post(path="/reviews", summary="Create a review",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"module_code","rating","title","content"},
     *       @OA\Property(property="module_code", type="string"),
     *       @OA\Property(property="rating", type="integer"),
     *       @OA\Property(property="title", type="string"),
     *       @OA\Property(property="content", type="string"),
     *       @OA\Property(property="workload", type="string"),
     *       @OA\Property(property="difficulty", type="string"),
     *       @OA\Property(property="usefulness", type="string")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Review created"),
     *   @OA\Response(response=400, description="Validation error"),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
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


            $rating = Validators::rating($data['rating'] ?? 0);
            $title = Validators::title($data['title'] ?? '');
            $content = Validators::text($data['content'] ?? '');


            $workload = isset($data['workload']) ? Validators::text($data['workload'], 50) : null;
            $difficulty = isset($data['difficulty']) ? Validators::text($data['difficulty'], 50) : null;
            $usefulness = isset($data['usefulness']) ? Validators::text($data['usefulness'], 50) : null;


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

    /**
     * @OA\Put(path="/reviews/{id}", summary="Edit own review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Review updated"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
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
                ? Validators::rating($data['rating'])
                : $review->rating;
            $title      = isset($data['title'])
                ? Validators::title($data['title'])
                : $review->title;
            $content    = isset($data['content'])
                ? Validators::text($data['content'])
                : $review->content;
            $workload   = isset($data['workload'])
                ? Validators::text($data['workload'], 50)
                : $review->workload;
            $difficulty = isset($data['difficulty'])
                ? Validators::text($data['difficulty'], 50)
                : $review->difficulty;
            $usefulness = isset($data['usefulness'])
                ? Validators::text($data['usefulness'], 50)
                : $review->usefulness;
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }

        $updated = ReviewRepository::update($review, compact(
            'rating', 'title', 'content', 'workload', 'difficulty', 'usefulness'
        ));

        Response::json(ReviewRepository::format($updated->load(['author', 'comments']), $userId));
    }

    /**
     * @OA\Post(path="/reviews/{id}/vote", summary="Toggle upvote/downvote",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"type"},
     *       @OA\Property(property="type", type="string", enum={"up","down"})
     *     )
     *   )),
     *   @OA\Response(response=200, description="Vote toggled"),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
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

    /**
     * @OA\Post(path="/reviews/{id}/report", summary="Toggle report on a review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(@OA\Property(property="reason", type="string"))
     *   )),
     *   @OA\Response(response=200, description="Report toggled")
     * )
     */
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

    /**
     * @OA\Post(path="/reviews/{id}/comments", summary="Add a comment to a review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"text"},
     *       @OA\Property(property="text", type="string")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Comment added")
     * )
     */
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