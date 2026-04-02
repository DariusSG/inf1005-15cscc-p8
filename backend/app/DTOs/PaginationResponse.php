<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PaginationResponse",
    type: "object",
    required: ["data", "meta"]
)]
class PaginationResponse
{
    #[OA\Property(
        property: "data",
        type: "array",
        items: new OA\Items(type: "object"),
        description: "List of paginated items"
    )]
    public array $data;

    #[OA\Property(
        property: "meta",
        ref: "#/components/schemas/PaginationMeta"
    )]
    public PaginationMeta $meta;
}
