<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleSemester extends Model
{
    protected $table = 'module_semesters';
    protected $fillable = ['module_code', 'semester'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }
}