<?php

namespace App\Controllers;

use App\Core\Response;
use App\Repositories\ReviewRepository;

class AdminController
{
    /**
     * @OA\Get(path="/admin/reported-reviews", summary="Get all reported reviews",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Array of reported reviews"),
     *   @OA\Response(response=403, description="Forbidden — admin only")
     * )
     */
    public function reportedReviews()
    {
        Response::json(ReviewRepository::reportedReviews());
    }
}