<?php

namespace App\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\HelpRequest;
use App\Models\HelpResponse;

class HelpRequestRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $search = $filters['search'] ?? null;

        $query = HelpRequest::with(['author:id,email', 'responses.author:id,email']);

        if ($search) {
            $escaped = BaseRepository::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('title LIKE ?', [$escaped])
                  ->orWhereRaw('module_code LIKE ?', [$escaped]);
            });
        }

        $total    = $query->count();
        $requests = $query
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => self::format($r))
            ->all();

        return [
            'data' => $requests,
            'meta' => BaseRepository::buildPaginationMeta($total, $perPage, $page),
        ];
    }

    public static function all(?string $search = null): array
    {
        $q = HelpRequest::with(['author:id,email', 'responses.author:id,email']);

        if ($search) {
            $q->where(function ($q) use ($search) {
                $escaped = '%' . addcslashes($search, '%_') . '%';
                $q->whereRaw('title LIKE ?', [$escaped])
                    ->orWhereRaw('module_code LIKE ?', [$escaped]);
            });
        }

        return $q->latest()->get()
            ->map(fn($r) => self::format($r))
            ->all();
    }

    public static function find(int $id): ?HelpRequest
    {
        return HelpRequest::with(['author:id,email', 'responses.author:id,email'])->find($id);
    }

    /**
     * Create a help request.
     *
     * Expected keys: user_id, title, module_code?, description?,
     *                urgency?, contact_email?, has_bounty?, bounty_amount?
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

    public static function addResponse(int $helpRequestId, int $userId, string $content): HelpResponse
    {
        return HelpResponse::create([
            'help_request_id' => $helpRequestId,
            'user_id'         => $userId,
            'content'         => $content,
        ]);
    }

    public static function markSolved(int $id): HelpRequest
    {
        $req = HelpRequest::findOrFail($id);
        $req->update(['status' => 'solved']);
        return $req->fresh(['author:id,email', 'responses.author:id,email']);
    }

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