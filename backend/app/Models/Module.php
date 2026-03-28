<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static count()
 * @method static create(array $data)
 */
class Module extends Model
{
    protected $table = 'modules';

    use SoftDeletes;

    protected $fillable = ['code', 'name', 'description', 'faculty', 'credits'];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'module_code', 'code');
    }

    public function prereqs(): BelongsToMany
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

    public function semesters(): HasMany
    {
        return $this->hasMany(ModuleSemester::class, 'module_code', 'code');
    }
}