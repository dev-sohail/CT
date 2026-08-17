<?php

namespace App\Domains\EntertainmentWritingSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EntertainmentWritingRecord extends Model
{
    protected $fillable = ['user_id', 'record_type', 'title', 'creator', 'status', 'rating', 'record_date', 'description', 'tags', 'details'];
    protected $casts = ['record_date' => 'date', 'rating' => 'integer', 'tags' => 'array', 'details' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
