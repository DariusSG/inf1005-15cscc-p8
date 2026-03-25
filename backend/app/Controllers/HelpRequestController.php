<?php

namespace App\Controllers;

use App\Core\Validators;
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? null;

        $result = HelpRequestRepository::paginate([
            'search' => $search,
        ], $perPage, $page);

        Response::json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * @OA\Get(path="/help-requests/{id}", summary="Get a single help request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Help request"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id)
    {
        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['error' => 'Not found'], 404);
        }
        Response::json(HelpRequestRepository::format($req));
    }

    /**
     * @OA\Post(path="/help-requests", summary="Create help request",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"title"},
     *       @OA\Property(property="title",         type="string"),
     *       @OA\Property(property="module_code",   type="string"),
     *       @OA\Property(property="description",   type="string"),
     *       @OA\Property(property="urgency",       type="string", enum={"low","medium","high"}),
     *       @OA\Property(property="contact_email", type="string"),
     *       @OA\Property(property="has_bounty",    type="boolean"),
     *       @OA\Property(property="bounty_amount", type="number")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Help request created")
     * )
     */
    public function store()
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        try {
            $title        = Validators::title($data['title'] ?? '');
            $moduleCode   = Validators::moduleCode($data['module_code'] ?? null);
            $description  = Validators::text($data['description'] ?? '', 5000);
            $bountyAmount = Validators::bountyAmount($data['bounty_amount'] ?? null);
            $urgency      = $data['urgency'] ?? 'low';
            if (!in_array($urgency, ['low', 'medium', 'high'], true)) {
                throw new \InvalidArgumentException('urgency must be low, medium, or high');
            }

            $contactEmail = Validators::email($data['contact_email'] ?? null);

            $req = HelpRequestRepository::create([
                'user_id'       => $userId,
                'title'         => $title,
                'module_code'   => $moduleCode,
                'description'   => $description,
                'urgency'       => $urgency,
                'contact_email' => $contactEmail,
                'has_bounty'    => (bool)($data['has_bounty'] ?? false),
                'bounty_amount' => $bountyAmount,
            ]);

            Response::json(HelpRequestRepository::format($req), 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);

        }
    }

    /**
     * @OA\Post(path="/help-requests/{id}/respond", summary="Add a response to a help request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(required={"content"},
     *       @OA\Property(property="content", type="string")
     *     )
     *   )),
     *   @OA\Response(response=201, description="Response added"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function respond(int $id)
    {
        $userId = JwtMiddleware::userId();
        $data   = Request::body();

        $content = trim($data['content'] ?? '');
        if (!$content) {
            Response::json(['error' => 'content is required'], 400);
        }

        if (!HelpRequestRepository::find($id)) {
            Response::json(['error' => 'Help request not found'], 404);
        }

        $response = HelpRequestRepository::addResponse($id, $userId, $content);
        Response::json($response->load('author:id,email')->toArray(), 201);
    }

    /**
     * @OA\Post(path="/help-requests/{id}/solve", summary="Mark a help request as solved",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Marked as solved"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function solve(int $id)
    {
        $userId = JwtMiddleware::userId();
        $role   = JwtMiddleware::userRole();

        $req = HelpRequestRepository::find($id);
        if (!$req) {
            Response::json(['error' => 'Help request not found'], 404);
        }

        // Only the owner or an admin can mark as solved
        if ($req->user_id !== $userId && $role !== 'admin') {
            Response::json(['error' => 'Forbidden'], 403);
        }

        Response::json(HelpRequestRepository::format(HelpRequestRepository::markSolved($id)));
    }
}