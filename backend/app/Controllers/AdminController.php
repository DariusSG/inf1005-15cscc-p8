<?php

namespace App\Controllers;

use App\Core\Response;
use App\Repositories\ReviewRepository;
use App\Repositories\BaseRepository;

class AdminController
{
    /**
     * @OA\Get(path="/admin/reported-reviews", summary="Get paginated reported reviews",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page",     in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Paginated reported reviews"),
     *   @OA\Response(response=403, description="Forbidden — admin only")
     * )
     */
    public function reportedReviews()
    {
        $page    = max(1, (int) ($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));

        $result = ReviewRepository::reportedReviewsPaginated($perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }
}