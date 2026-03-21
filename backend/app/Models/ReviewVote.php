<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewVote extends Model
{
    protected $table = 'review_votes';
    protected $fillable = ['review_id', 'user_id', 'type'];
}