<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ErrorResponse",
    type: "object",
    required: ["message", "code"]
)]
class ErrorResponse
{
    #[OA\Property(
        property: "message",
        type: "string",
        description: "Human-readable error message",
        example: "Invalid credentials"
    )]
    public string $message;

    #[OA\Property(
        property: "code",
        type: "string",
        description: "Machine-readable error code",
        example: "AUTH_INVALID_CREDENTIALS"
    )]
    public string $code;
}
