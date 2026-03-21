<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\HelpRequestRepository;

class HelpRequestController
{
    /**
     * @OA\Get(path="/help-requests", summary="List help requests",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Array of help requests")
     * )
     */
    public function index()
    {
        $search = $_GET['search'] ?? null;
        Response::json(HelpRequestRepository::all($search));
    }

    /**
     * @OA\Post(path="/help-requests", summary="Create help request",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"title"},
     *       @OA\Property(property="title", type="string"),
     *       @OA\Property(property="module_code", type="string"),
     *       @OA\Property(property="description", type="string")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Help request created")
     * )
     */
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        $title = trim($data['title'] ?? '');
        if (!$title) {
            Response::json(['error' => 'title is required'], 400);
        }

        $req = HelpRequestRepository::create([
            'user_id'     => $userId,
            'title'       => $title,
            'module_code' => isset($data['module_code']) ? strtoupper(trim($data['module_code'])) : null,
            'description' => $data['description'] ?? null,
        ]);

        Response::json($req->toArray(), 201);
    }
}