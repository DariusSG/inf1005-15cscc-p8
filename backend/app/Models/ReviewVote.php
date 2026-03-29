<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, int $reviewId)
 * @method static updateOrCreate(int[] $array, string[] $array1)
 */
class ReviewVote extends Model
{
    protected $table = 'review_votes';
    protected $fillable = ['review_id', 'user_id', 'type'];
}