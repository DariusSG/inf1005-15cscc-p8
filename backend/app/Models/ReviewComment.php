<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewComment extends Model
{
    protected $table = 'review_comments';
    protected $fillable = ['review_id', 'user_id', 'text'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}