<?php

namespace App\Domains\ExpenseTrackingAndBudgetingEngine\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['user_id', 'spent_on', 'description', 'category', 'amount', 'currency', 'payment_method', 'recurring', 'tags', 'notes'];
    protected $casts = ['spent_on' => 'date', 'amount' => 'decimal:2', 'recurring' => 'boolean', 'tags' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
