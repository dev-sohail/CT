<?php

namespace App\Domains\HomeTravelSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeTravelRecord extends Model
{
    protected $fillable = ['user_id', 'record_type', 'name', 'description', 'location', 'status', 'date', 'due_date', 'amount', 'unit', 'details', 'metadata'];
    protected $casts = ['date' => 'date', 'due_date' => 'date', 'amount' => 'decimal:2', 'details' => 'array', 'metadata' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
