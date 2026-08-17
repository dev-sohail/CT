<?php

namespace App\Domains\CareerBusinessSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CareerBusinessRecord extends Model
{
    protected $fillable = ['user_id', 'record_type', 'title', 'organization', 'status', 'record_date', 'next_date', 'amount', 'description', 'details', 'metadata'];
    protected $casts = ['record_date' => 'date', 'next_date' => 'date', 'amount' => 'decimal:2', 'details' => 'array', 'metadata' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
