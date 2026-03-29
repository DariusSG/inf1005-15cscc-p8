<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string $hash)
 * @method static create(array $array)
 */
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