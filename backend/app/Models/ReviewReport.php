<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, int $reviewId)
 * @method static create(array $array)
 */
class ReviewReport extends Model
{
    protected $table = 'review_reports';
    protected $fillable = ['review_id', 'user_id', 'reason'];
}