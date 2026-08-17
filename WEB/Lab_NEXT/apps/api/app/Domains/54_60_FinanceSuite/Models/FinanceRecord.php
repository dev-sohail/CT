<?php

namespace App\Domains\FinanceSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinanceRecord extends Model
{
    protected $fillable = ['user_id', 'record_type', 'name', 'amount', 'target_amount', 'current_amount', 'currency', 'status', 'due_date', 'institution', 'category', 'notes', 'metadata'];
    protected $casts = ['amount' => 'decimal:2', 'target_amount' => 'decimal:2', 'current_amount' => 'decimal:2', 'due_date' => 'date', 'metadata' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
