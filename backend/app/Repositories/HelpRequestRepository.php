<?php

namespace App\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\HelpRequest;
use App\Models\HelpResponse;
use Throwable;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "HelpRequest",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "userEmail", type: "string"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "module", type: "string"),
        new OA\Property(property: "desc", type: "string"),
        new OA\Property(property: "urgency", type: "string", enum: ["low", "medium", "high"]),
        new OA\Property(property: "contactEmail", type: "string", format: "email"),
        new OA\Property(property: "hasBounty", type: "boolean"),
        new OA\Property(property: "bountyAmount", type: "number"),
        new OA\Property(property: "status", type: "string"),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
#[OA\Schema(
    schema: "HelpRequestResponse",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "help_request_id", type: "integer"),
        new OA\Property(property: "user_id", type: "integer"),
        new OA\Property(property: "content", type: "string"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(
            property: "author",
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "email", type: "string")
            ]
        )
    ]
)]
class HelpRequestRepository extends BaseRepository
{
    /**
     * Search HelpRequest with Pagination
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $moduleCode = $filters['module_code'] ?? null;
        $search     = $filters['search'] ?? null;
        $authorId   = $filters['author_id'] ?? null;

        $query = HelpRequest::with(['author:id,email', 'responses.author:id,email']);

        if ($moduleCode) {
            $query->where('module_code', $moduleCode);
        }

        if ($authorId) {
            $query->where('user_id', $authorId);
        }

        if ($search) {
            $escaped = self::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('title LIKE ?', [$escaped])
                ->orWhereRaw('description LIKE ?', [$escaped])
                ->orWhereRaw('module_code LIKE ?', [$escaped])
                ->orWhereHas('module', fn($mq) => $mq
                    ->whereRaw('code LIKE ?', [$escaped])
                    ->orWhereRaw('name LIKE ?', [$escaped])
                    ->orWhereRaw('description LIKE ?', [$escaped])
                );
            });
        }

        $paginator = $query
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $requests = collect($paginator->items())
            ->map(fn($r) => self::format($r))
            ->all();

        return [
            'data' => $requests,
            'meta' => self::buildPaginationMeta(
                $paginator->total(),
                $perPage,
                $page
            ),
        ];
    }

    /**
     * Generic Search for HelpRequest
     *
     * @param string|null $search
     * @return array
     */
    public static function all(?string $search = null): array
    {
        $q = HelpRequest::with(['author:id,email', 'responses.author:id,email']);

        if ($search) {
            $escaped = self::escapeSearch($search);
            $q->where(function ($q) use ($escaped) {
                $q->whereRaw('title LIKE ?', [$escaped])
                    ->orWhereRaw('description LIKE ?', [$escaped])
                    ->orWhereRaw('module_code LIKE ?', [$escaped])
                    ->orWhereHas('module', fn($mq) => $mq
                        ->whereRaw('code LIKE ?', [$escaped])
                        ->orWhereRaw('name LIKE ?', [$escaped])
                        ->orWhereRaw('description LIKE ?', [$escaped])
                    );
            });
        }

        /** @var HelpRequest $r */
        return $q->latest()->get()
            ->map(fn($r) => self::format($r))
            ->all();
    }

    /**
     * Find HelpRequest given id
     *
     * @param int $id
     * @return HelpRequest|null
     */
    public static function find(int $id): ?HelpRequest
    {
        return HelpRequest::with(['author:id,email', 'responses.author:id,email'])->find($id);
    }


    /**
     * Update a HelpRequest
     */
    public static function update(int $id, array $data): HelpRequest
    {
        $req = HelpRequest::findOrFail($id);
        $req->update($data);
        return $req->fresh(['author:id,email', 'responses.author:id,email']);
    }

    /**
     * Create new HelpRequest
     *
     * @param array $data
     * @return HelpRequest
     * @throws Throwable
     */
    public static function create(array $data): HelpRequest
    {
        return Capsule::connection()->transaction(function () use ($data) {
            $req = HelpRequest::create(array_merge([
                'urgency'    => 'low',
                'has_bounty' => false,
                'status'     => 'open',
            ], $data));

            return $req->load(['author:id,email', 'responses']);
        });
    }

    /**
     * Add Response to HelpRequest
     *
     * @param int $helpRequestId
     * @param int $userId
     * @param string $content
     * @return HelpResponse
     */
    public static function addResponse(int $helpRequestId, int $userId, string $content): HelpResponse
    {
        return HelpResponse::create([
            'help_request_id' => $helpRequestId,
            'user_id'         => $userId,
            'content'         => $content,
        ]);
    }

    /**
     * Mark HelpRequest as solved
     *
     * @param int $id
     * @return HelpRequest
     */
    public static function markSolved(int $id): HelpRequest
    {
        $req = HelpRequest::findOrFail($id);
        $req->update(['status' => 'solved']);
        return $req->fresh(['author:id,email', 'responses.author:id,email']);
    }

    public static function delete(int $id): bool
    {
        $req = HelpRequest::find($id);
        if (!$req) {
            return false;
        }

        return (bool) $req->delete();
    }

    /**
     * Array formatting for JSON
     *
     * @param HelpRequest $req
     * @return array
     */
    public static function format(HelpRequest $req): array
    {
        return [
            'id'           => $req->id,
            'userEmail'    => $req->author?->email,
            'title'        => $req->title,
            'module'       => $req->module_code,
            'desc'         => $req->description,
            'urgency'      => $req->urgency,
            'contactEmail' => $req->contact_email,
            'hasBounty'    => (bool)$req->has_bounty,
            'bountyAmount' => $req->bounty_amount,
            'status'       => $req->status,
            'responses'    => $req->responses->map(fn($r) => [
                'id'      => $r->id,
                'author'  => $r->author?->email,
                'content' => $r->content,
                'time'    => $r->created_at,
            ])->values()->all(),
            'created_at'   => $req->created_at,
        ];
    }
}