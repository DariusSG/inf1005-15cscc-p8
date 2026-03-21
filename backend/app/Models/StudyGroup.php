<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyGroup extends Model
{
    protected $table = 'study_groups';
    protected $fillable = ['user_id', 'name', 'module_code', 'description', 'meeting_time', 'location'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}