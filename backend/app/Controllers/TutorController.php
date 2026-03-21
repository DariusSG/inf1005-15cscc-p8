<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\TutorRepository;

class TutorController
{
    /**
     * @OA\Get(path="/tutors", summary="List tutors",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Array of tutors")
     * )
     */
    public function index()
    {
        $search = $_GET['search'] ?? null;
        Response::json(TutorRepository::all($search));
    }

    /**
     * @OA\Post(path="/tutors", summary="Create tutor listing",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"name"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="module_code", type="string"),
     *       @OA\Property(property="contact", type="string"),
     *       @OA\Property(property="bio", type="string"),
     *       @OA\Property(property="rate", type="number")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Tutor listing created")
     * )
     */
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        $name = trim($data['name'] ?? '');
        if (!$name) {
            Response::json(['error' => 'name is required'], 400);
        }

        $tutor = TutorRepository::create([
            'user_id'     => $userId,
            'name'        => $name,
            'module_code' => isset($data['module_code']) ? strtoupper(trim($data['module_code'])) : null,
            'contact'     => $data['contact'] ?? null,
            'bio'         => $data['bio']     ?? null,
            'rate'        => isset($data['rate']) ? (float)$data['rate'] : null,
        ]);

        Response::json($tutor->toArray(), 201);
    }
}