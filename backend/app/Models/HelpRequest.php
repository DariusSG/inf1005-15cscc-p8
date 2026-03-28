<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array|bool[]|string[] $array_merge)
 * @method static findOrFail(int $id)
 */

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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(HelpResponse::class);
    }
}