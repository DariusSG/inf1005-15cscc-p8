<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $table = 'tutors';
    protected $fillable = ['user_id', 'name', 'module_code', 'contact', 'bio', 'rate'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}