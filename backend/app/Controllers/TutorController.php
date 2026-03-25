<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validators;
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? null;

        $result = TutorRepository::paginate([
            'search' => $search,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * @OA\Post(path="/tutors", summary="Create tutor listing",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"name"},
     *       @OA\Property(property="name",          type="string"),
     *       @OA\Property(property="module_codes",  type="array",
     *         @OA\Items(type="string")),
     *       @OA\Property(property="contact_email", type="string"),
     *       @OA\Property(property="bio",           type="string"),
     *       @OA\Property(property="rate",          type="number")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Tutor listing created")
     * )
     */
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $name         = Validators::title($data['name'] ?? '', 100);
            $contactEmail = Validators::email($data['contact_email'] ?? null);
            $bio          = isset($data['bio']) ? Validators::text($data['bio'], 1000) : null;
            $rate         = Validators::rate($data['rate'] ?? null);
            $moduleCodes  = array_filter(
                array_map('strtoupper', (array) ($data['module_codes'] ?? [])),
                fn($c) => preg_match('/^[A-Za-z0-9]{2,10}$/', $c)
            );

            $tutor = TutorRepository::create([
                'user_id'       => $userId,
                'name'          => $name,
                'contact_email' => $contactEmail,
                'bio'           => $bio,
                'rate'          => $rate,
                'module_codes'  => array_values($moduleCodes),
            ]);

            Response::json(TutorRepository::format($tutor), 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}