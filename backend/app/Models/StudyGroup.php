<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $data)
 */
class StudyGroup extends Model
{
    protected $table = 'study_groups';

    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'module_code', 'description', 'meeting_time', 'location'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'study_group_members', 'study_group_id', 'user_id')
            ->withTimestamps();
    }
}