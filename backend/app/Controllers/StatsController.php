<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Module;
use App\Models\Review;
use App\Models\Tutor;
use App\Models\HelpRequest;

class StatsController
{
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
