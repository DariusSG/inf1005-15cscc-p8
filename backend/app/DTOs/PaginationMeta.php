<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PaginationMeta",
    type: "object",
    required: ["total", "per_page", "current_page", "last_page"]
)]
class PaginationMeta
{
    #[OA\Property(
        property: "total",
        type: "integer",
        description: "Total number of items",
        example: 150
    )]
    public int $total;

    #[OA\Property(
        property: "per_page",
        type: "integer",
        description: "Items per page",
        example: 20
    )]
    public int $per_page;

    #[OA\Property(
        property: "current_page",
        type: "integer",
        description: "Current page number",
        example: 1
    )]
    public int $current_page;

    #[OA\Property(
        property: "last_page",
        type: "integer",
        description: "Last page number",
        example: 8
    )]
    public int $last_page;
}
