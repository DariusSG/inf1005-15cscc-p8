<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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