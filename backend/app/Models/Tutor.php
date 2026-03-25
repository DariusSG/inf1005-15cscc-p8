<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $table = 'tutors';

    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'contact_email', 'bio', 'rate'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modules()
    {
        return $this->belongsToMany(
            Module::class,
            'tutor_modules',
            'tutor_id',
            'module_code',
            'id',
            'code'
        );
    }
}