<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    protected $fillable = ['code', 'name', 'description', 'credits'];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'module_code', 'code');
    }
}