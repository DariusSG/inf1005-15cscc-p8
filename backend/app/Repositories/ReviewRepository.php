<?php

namespace App\Repositories;

use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\ReviewReport;
use App\Models\ReviewComment;

class ReviewRepository
{
    public static function forModule(string $moduleCode, int $userId): array
    {
        return Review::with(['author:id,email', 'comments.author:id,email'])
            ->where('module_code', $moduleCode)
            ->latest()
            ->get()
            ->map(fn($r) => self::format($r, $userId))
            ->all();
    }

    public static function find(int $id): ?Review
    {
        return Review::with(['author:id,email', 'comments.author:id,email'])->find($id);
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

    public static function userVote(int $reviewId, int $userId): ?ReviewVote
    {
        return ReviewVote::where('review_id', $reviewId)->where('user_id', $userId)->first();
    }

    public static function toggleVote(int $reviewId, int $userId, string $type): array
    {
        $vote     = self::userVote($reviewId, $userId);
        $review   = Review::find($reviewId);
        $previous = $vote?->type;

        if ($previous === $type) {
            // undo vote
            $vote->delete();
            $type === 'up' ? $review->decrement('upvotes') : $review->decrement('downvotes');
        } else {
            // undo opposite then apply new
            if ($previous === 'up')   $review->decrement('upvotes');
            if ($previous === 'down') $review->decrement('downvotes');

            ReviewVote::updateOrCreate(
                ['review_id' => $reviewId, 'user_id' => $userId],
                ['type' => $type]
            );

            $type === 'up' ? $review->increment('upvotes') : $review->increment('downvotes');
        }

        return $review->fresh()->toArray();
    }

    public static function toggleReport(int $reviewId, int $userId, ?string $reason): bool
    {
        $existing = ReviewReport::where('review_id', $reviewId)->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            return false; // un-reported
        }

        ReviewReport::create(['review_id' => $reviewId, 'user_id' => $userId, 'reason' => $reason]);
        return true; // reported
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
            ->with('author:id,email')
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

    public static function format(Review $review, int $userId): array
    {
        $vote = self::userVote($review->id, $userId);
        return array_merge($review->toArray(), [
            'author_name' => $review->author?->email,
            'user_vote'   => $vote?->type,
            'reported'    => ReviewReport::where('review_id', $review->id)
                                ->where('user_id', $userId)->exists(),
        ]);
    }
}