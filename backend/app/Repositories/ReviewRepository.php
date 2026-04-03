<?php

namespace App\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\ReviewReport;
use App\Models\ReviewComment;
use Throwable;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ReportedReviews',
    type: 'object',
    properties: [
        new OA\Property(property: "id", type: "integer", description: "Review ID"),
        new OA\Property(property: "module_code", type: "string"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "author", type: "string"),
        new OA\Property(property: "report_count", type: "integer"),
        new OA\Property(property: "users", type: "array", items: new OA\Items(type: "integer"))
    ]
)]
#[OA\Schema(
    schema: "Review",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "module_code", type: "string"),
        new OA\Property(property: "author", type: "string", description: "Shortname of author"),
        new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5),
        new OA\Property(property: "upvotes", type: "integer"),
        new OA\Property(property: "downvotes", type: "integer"),
        new OA\Property(
            property: "comments",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "a", type: "string", description: "Author shortname"),
                    new OA\Property(property: "t", type: "string", description: "Text content"),
                    new OA\Property(property: "time", type: "string", format: "date-time")
                ]
            )
        ),
        new OA\Property(
            property: "voters",
            type: "object",
            additionalProperties: new OA\AdditionalProperties(type: "string", enum: ["up", "down"]),
            description: "Map of userId => voteType"
        ),
        new OA\Property(
            property: "reportedBy",
            type: "array",
            items: new OA\Items(type: "integer")
        ),
        new OA\Property(property: "user_vote", type: "string", nullable: true, enum: ["up", "down"]),
        new OA\Property(property: "reported", type: "boolean")
    ]
)]
#[OA\Schema(
    schema: "ReviewComment",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "review_id", type: "integer"),
        new OA\Property(property: "text", type: "string"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "author", type: "object", properties: [
            new OA\Property(property: "id", type: "integer"),
            new OA\Property(property: "email", type: "string")
        ])
    ]
)]
class ReviewRepository extends BaseRepository
{
    public static function paginate(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $userId     = $filters['user_id'] ?? 0;
        $moduleCode = $filters['module_code'] ?? null;
        $search     = $filters['search'] ?? null;
        $authorId   = $filters['author_id'] ?? null;

        $query = Review::with([
            'author:id,email,name',
            'comments.author:id,email,name',
            'votes',
            'reports'
        ]);

        if ($moduleCode) {
            $query->where('module_code', $moduleCode);
        }

        if ($search) {
            $escaped = self::escapeSearch($search);
            $query->where(function ($q) use ($escaped) {
                $q->whereRaw('title LIKE ?', [$escaped])
                ->orWhereRaw('content LIKE ?', [$escaped])
                ->orWhereRaw('module_code LIKE ?', [$escaped])
                ->orWhereRaw('difficulty LIKE ?', [$escaped])
                ->orWhereRaw('usefulness LIKE ?', [$escaped])
                ->orWhereRaw('workload LIKE ?', [$escaped])
                ->orWhereHas('module', fn($mq) => $mq
                    ->whereRaw('code LIKE ?', [$escaped])
                    ->orWhereRaw('name LIKE ?', [$escaped])
                    ->orWhereRaw('description LIKE ?', [$escaped])
                );
            });
        }

        if ($authorId) {
            $query->where('user_id', $authorId);
        }

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $reviews = collect($paginator->items())
            ->map(fn($r) => self::format($r, $userId))
            ->all();

        return [
            'data' => $reviews,
            'meta' => self::buildPaginationMeta(
                $paginator->total(),
                $perPage,
                $page
            ),
        ];
    }

    public static function forModule(string $moduleCode, int $userId): array
    {
        /** @var Review $r */
        return Review::with(['author:id,email,name', 'comments.author:id,email,name', 'votes', 'reports'])
            ->where('module_code', $moduleCode)
            ->latest()
            ->get()
            ->map(fn($r) => self::format($r, $userId))
            ->all();
    }

    public static function moduleAggregates(string $moduleCode): array
    {
        $base = Review::where('module_code', $moduleCode);

        return [
            'avg_rating' => self::roundAvg((clone $base)->avg('rating')),
            'avg_workload' => self::roundAvg((clone $base)->avg('workload')),
            'avg_difficulty' => self::roundAvg((clone $base)->avg('difficulty')),
            'avg_usefulness' => self::roundAvg((clone $base)->avg('usefulness')),
        ];
    }

    public static function find(int $id): ?Review
    {
        return Review::with(['author:id,email,name', 'comments.author:id,email,name', 'votes', 'reports'])->find($id);
    }

    public static function create(array $data): Review
    {
        return Review::create($data);
    }

    public static function update(Review $review, array $data): Review
    {
        $review->update($data);
        return $review->fresh();
    }

    public static function delete(int $id): bool
    {
        $review = Review::find($id);
        if (!$review) {
            return false;
        }
        return (bool) $review->delete();
    }

    public static function userVote(int $reviewId, int $userId): ?ReviewVote
    {
        return ReviewVote::where('review_id', $reviewId)->where('user_id', $userId)->first();
    }

    /**
     * Toggle vote. Returns formatted review array (votes computed, no counter columns).
     * @throws Throwable
     */
    public static function toggleVote(int $reviewId, int $userId, string $type): array
    {
        return Capsule::connection()->transaction(function () use ($reviewId, $userId, $type) {
            $existing = self::userVote($reviewId, $userId);


            if ($existing && $existing->type === $type) {
                // Same type → undo vote
                $existing->delete();
            } else {
                // Different type or no prior vote → upsert
                ReviewVote::updateOrCreate(
                    ['review_id' => $reviewId, 'user_id' => $userId],
                    ['type' => $type]
                );
            }


            $review = self::find($reviewId);
            return self::format($review, $userId);
        });
    }

    public static function toggleReport(int $reviewId, int $userId, ?string $reason): bool
    {
        $existing = ReviewReport::where('review_id', $reviewId)->where('user_id', $userId)->first();

        if ($existing) {
            return true; // Already reported
        }

        ReviewReport::create(['review_id' => $reviewId, 'user_id' => $userId, 'reason' => $reason]);
        return true;
    }

    public static function removeReport(int $reviewId, int $userId): bool
    {
        $existing = ReviewReport::where('review_id', $reviewId)->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            return true;   
        }

        return false;
    }

    public static function addComment(int $reviewId, int $userId, string $text): ReviewComment
    {
        return ReviewComment::create([
            'review_id' => $reviewId,
            'user_id'   => $userId,
            'text'      => $text,
        ]);
    }

    public static function reportedReviews(): array
    {
        return Review::withCount('reports')
            ->having('reports_count', '>', 0)
            ->with('author:id,email,name')
            ->get()
            ->map(fn($r) => [
                'id'           => $r->id,
                'module_code'  => $r->module_code,
                'title'        => $r->title,
                'author'       => $r->author?->email,
                'report_count' => $r->reports_count,
            ])
            ->all();
    }

    public static function reportedReviewsPaginated(int $perPage = 20, int $page = 1): array
    {
        $query = Review::withCount('reports')
            ->having('reports_count', '>', 0)
            ->with('author:id,email,name');

        $total = (clone $query)->count();

        $items = $query
            ->orderByDesc('reports_count')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => [
                'id'           => $r->id,
                'module_code'  => $r->module_code,
                'title'        => $r->title,
                'author'       => $r->author?->email,
                'report_count' => $r->reports_count,
                'users'        => $r->reports->pluck('user_id')->values()->all(),
            ])
            ->all();

        return [
            'data' => $items,
            'meta' => BaseRepository::buildPaginationMeta($total, $perPage, $page),
        ];
    }

    /**
     * Format a review for API response.
     *
     * Produces:
     *   author      — shortname (name field, falls back to email prefix)
     *   email       — full author email
     *   upvotes     — computed from votes relation
     *   downvotes   — computed from votes relation
     *   voters      — map of user_id => vote type  { "12": "up", "34": "down" }
     *   reportedBy  — array of user_ids who reported
     *   comments    — array with a (shortname), t (text), time fields
     *   date        — created_at alias
     */
    public static function format(Review $review, int $userId): array
    {
        // Ensure votes & reports relations are loaded
        if (!$review->relationLoaded('votes'))   $review->load('votes');
        if (!$review->relationLoaded('reports'))  $review->load('reports');
        if (!$review->relationLoaded('author'))   $review->load('author:id,email,name');
        if (!$review->relationLoaded('comments')) $review->load('comments.author:id,email,name');

        $upvotes   = $review->votes->where('type', 'up')->count();
        $downvotes = $review->votes->where('type', 'down')->count();

        // voters{} — keyed by user_id string
        $voters = $review->votes->mapWithKeys(
            fn($v) => [(string)$v->user_id => $v->type]
        )->all();

        // reportedBy[] — array of user_id ints
        $reportedBy = $review->reports->pluck('user_id')->values()->all();

        // comments with a/t/time shape
        $comments = $review->comments->map(fn($c) => [
            'a'    => self::shortname($c->author),
            't'    => $c->text,
            'time' => $c->created_at,
        ])->values()->all();

        return [
            'id'         => $review->id,
            'user_id'    => $review->author ? $review->user_id : -1,
            'module_code'=> $review->module_code,
            'author'     => self::shortname($review->author),
            'email'      => $review->author?->email,
            'rating'     => $review->rating,
            'title'      => $review->title,
            'content'    => $review->content,
            'workload'   => $review->workload,
            'difficulty' => $review->difficulty,
            'usefulness' => $review->usefulness,
            'upvotes'    => $upvotes,
            'downvotes'  => $downvotes,
            'date'       => $review->created_at,
            'comments'   => $comments,
            'voters'     => $voters,
            'reportedBy' => $reportedBy,
            // Convenience for current user
            'user_vote'  => $review->votes->firstWhere('user_id', $userId)?->type,
            'reported'   => in_array($userId, $reportedBy),
        ];
    }

    private static function roundAvg(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round((float) $value, 1);
    }


    /** Returns the user's name if set, otherwise the local part of their email. */
    private static function shortname(?object $user): ?string
    {
        if (!$user) return null;
        return $user->name ?: explode('@', $user->email)[0];
    }
}