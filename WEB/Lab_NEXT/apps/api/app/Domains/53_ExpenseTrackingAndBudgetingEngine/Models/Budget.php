<?php

namespace App\Domains\ExpenseTrackingAndBudgetingEngine\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['user_id', 'category', 'amount', 'period', 'starts_on', 'active'];
    protected $casts = ['amount' => 'decimal:2', 'starts_on' => 'date', 'active' => 'boolean'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
