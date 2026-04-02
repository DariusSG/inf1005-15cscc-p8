<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static count()
 * @method static select(string ...$fields)
 * @method static create(array $array)
 */
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

    public function isDeleted(): bool
    {
        return $this->trashed();
    }
}