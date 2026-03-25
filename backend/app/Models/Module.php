<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';

    use SoftDeletes;

    protected $fillable = ['code', 'name', 'description', 'faculty', 'credits'];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'module_code', 'code');
    }

    public function prereqs()
    {
        return $this->belongsToMany(
            Module::class,
            'module_prereqs',
            'module_code',
            'prereq_code',
            'code',
            'code'
        );
    }

    public function semesters()
    {
        return $this->hasMany(ModuleSemester::class, 'module_code', 'code');
    }
}