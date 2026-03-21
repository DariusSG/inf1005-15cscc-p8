<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpRequest extends Model
{
    protected $table = 'help_requests';
    protected $fillable = ['user_id', 'module_code', 'title', 'description', 'resolved'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}