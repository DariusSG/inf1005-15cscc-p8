<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleSemester extends Model
{
    protected $table = 'module_semesters';
    protected $fillable = ['module_code', 'semester'];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }
}