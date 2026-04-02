<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "INF1005 API",
    description: "Backend API for modules, tutors, study groups, reviews, and authentication"
)]
#[OA\Server(
    url: "/api/v1",
    description: "API v1"
)]
final class OpenApiSpec
{
}
