<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyGroup extends Model
{
    protected $table = 'study_groups';

    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'module_code', 'description', 'meeting_time', 'location'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

class Session extends Model
{
    protected $table = 'sessions';

    protected $fillable = [
        'user_id',
        'jti',
        'refresh_jti',
        'expires_at',
        'ip',
        'user_agent',
        'revoked'
    ];

    public $timestamps = true;
}

class ReviewVote extends Model
{
    protected $table = 'review_votes';
    protected $fillable = ['review_id', 'user_id', 'type'];
}

class ReviewReport extends Model
{
    protected $table = 'review_reports';
    protected $fillable = ['review_id', 'user_id', 'reason'];
}

class ReviewComment extends Model
{
    protected $table = 'review_comments';
    protected $fillable = ['review_id', 'user_id', 'text'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

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

class RefreshToken extends Model
{
    protected $table = 'refresh_tokens';

    protected $fillable = [
        'user_id',
        'jti',
        'token_hash',
        'expires_at',
        'revoked'
    ];

    public $timestamps = true;
}

class ModuleSemester extends Model
{
    protected $table = 'module_semesters';
    protected $fillable = ['module_code', 'semester'];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }
}

class Module extends Model
{
    protected $table = 'modules';

    use SoftDeletes;

    protected $fillable = ['code', 'name', 'description', 'faculty', 'credits'];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'module_code', 'code');
    }

    public function prereqs()
    {
        return $this->belongsToMany(
            Module::class,
            'module_prereqs',
            'module_code',
            'prereq_code',
            'code',
            'code'
        );
    }

    public function semesters()
    {
        return $this->hasMany(ModuleSemester::class, 'module_code', 'code');
    }
}

class HelpResponse extends Model
{
    protected $table = 'help_responses';
    protected $fillable = ['help_request_id', 'user_id', 'content'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function helpRequest()
    {
        return $this->belongsTo(HelpRequest::class);
    }
}

class HelpRequest extends Model
{
    protected $table = 'help_requests';

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'module_code',
        'title',
        'description',
        'urgency',
        'contact_email',
        'has_bounty',
        'bounty_amount',
        'status',
    ];

    protected $casts = [
        'has_bounty'    => 'boolean',
        'bounty_amount' => 'float',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }

    public function responses()
    {
        return $this->hasMany(HelpResponse::class);
    }
}

class EmailVerification extends Model
{
    protected $table = 'email_verifications';

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'boolean',
    ];
}