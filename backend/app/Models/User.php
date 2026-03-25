<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    use SoftDeletes;

    protected $fillable = [
        'email',
        'name',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];
}