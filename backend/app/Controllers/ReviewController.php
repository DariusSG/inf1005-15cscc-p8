<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\ReviewRepository;
use App\Repositories\ModuleRepository;

class ReviewController
{
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

        $moduleCode = strtoupper(trim($data['module_code'] ?? ''));
        if (!$moduleCode || !ModuleRepository::findByCode($moduleCode)) {
            Response::json(['error' => 'Module not found'], 404);
        }

        $rating = (int)($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            Response::json(['error' => 'Rating must be between 1 and 5'], 400);
        }

        if (empty(trim($data['title'] ?? ''))) {
            Response::json(['error' => 'Title is required'], 400);
        }

        if (empty(trim($data['content'] ?? ''))) {
            Response::json(['error' => 'Content is required'], 400);
        }

        $review = ReviewRepository::create([
            'module_code' => $moduleCode,
            'user_id'     => $userId,
            'rating'      => $rating,
            'title'       => trim($data['title']),
            'content'     => trim($data['content']),
            'workload'    => $data['workload']   ?? null,
            'difficulty'  => $data['difficulty'] ?? null,
            'usefulness'  => $data['usefulness'] ?? null,
        ]);

        Response::json(ReviewRepository::format($review->fresh(['author', 'comments']), $userId), 201);
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

        $rating = isset($data['rating']) ? (int)$data['rating'] : $review->rating;
        if ($rating < 1 || $rating > 5) {
            Response::json(['error' => 'Rating must be between 1 and 5'], 400);
        }

        $updated = ReviewRepository::update($review, [
            'rating'     => $rating,
            'title'      => trim($data['title']   ?? $review->title),
            'content'    => trim($data['content'] ?? $review->content),
            'workload'   => $data['workload']   ?? $review->workload,
            'difficulty' => $data['difficulty'] ?? $review->difficulty,
            'usefulness' => $data['usefulness'] ?? $review->usefulness,
        ]);

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