<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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