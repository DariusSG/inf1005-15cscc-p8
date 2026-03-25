<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

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