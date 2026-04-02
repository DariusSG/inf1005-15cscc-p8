<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $data)
 * @method static withCount(string $string)
 */
class Review extends Model
{
    protected $table = 'reviews';

    use SoftDeletes;

    protected $fillable = [
        'module_code', 'user_id', 'rating', 'title',
        'content', 'workload', 'difficulty', 'usefulness',
    ];

    // upvotes/downvotes are no longer stored columns — computed from review_votes
    protected $appends = ['upvotes', 'downvotes'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }

    public function comments()
    {
        return $this->hasMany(ReviewComment::class);
    }

    public function votes()
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function reports()
    {
        return $this->hasMany(ReviewReport::class);
    }

    // ── Computed vote counts ──────────────────────────────────────────────

    public function getUpvotesAttribute(): int
    {
        // Uses loaded relation if available (avoids N+1 when eager-loaded)
        if ($this->relationLoaded('votes')) {
            return $this->votes->where('type', 'up')->count();
        }
        return $this->votes()->where('type', 'up')->count();
    }

    public function getDownvotesAttribute(): int
    {
        if ($this->relationLoaded('votes')) {
            return $this->votes->where('type', 'down')->count();
        }
        return $this->votes()->where('type', 'down')->count();
    }
}