<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Module;
use App\Models\Review;
use App\Models\Tutor;
use App\Models\HelpRequest;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Stats',
    description: 'Public platform statistics'
)]
class StatsController
{
    #[OA\Get(
        path: "/stats",
        summary: "Get platform statistics",
        tags: ["Stats"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Statistics",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "modules", type: "integer"),
                        new OA\Property(property: "tutors", type: "integer"),
                        new OA\Property(property: "reviews", type: "integer"),
                        new OA\Property(property: "open_help", type: "integer"),
                        new OA\Property(property: "avg_rating", type: "number", nullable: true)
                    ]
                )
            )
        ]
    )]
    public function index(): void
    {
        $moduleCount   = Module::count();
        $tutorCount    = Tutor::count();
        $reviewCount   = Review::count();
        $openHelpCount = HelpRequest::where('status', 'open')->count();
        $avgRating     = $reviewCount > 0
            ? round(Review::avg('rating'), 1)
            : null;

        Response::json([
            'modules'       => $moduleCount,
            'tutors'        => $tutorCount,
            'reviews'       => $reviewCount,
            'open_help'     => $openHelpCount,
            'avg_rating'    => $avgRating,
        ]);
    }
}
