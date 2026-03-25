<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpResponse extends Model
{
    protected $table = 'help_responses';
    protected $fillable = ['help_request_id', 'user_id', 'content'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function helpRequest()
    {
        return $this->belongsTo(HelpRequest::class);
    }
}