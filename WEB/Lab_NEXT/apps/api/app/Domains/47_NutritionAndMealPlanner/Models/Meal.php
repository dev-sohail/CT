<?php

namespace App\Domains\NutritionAndMealPlanner\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meal extends Model
{
    protected $fillable = ['user_id', 'eaten_on', 'meal_type', 'name', 'calories', 'protein_grams', 'carbs_grams', 'fat_grams', 'ingredients', 'notes', 'metadata'];
    protected $casts = ['eaten_on' => 'date', 'calories' => 'decimal:2', 'protein_grams' => 'decimal:2', 'carbs_grams' => 'decimal:2', 'fat_grams' => 'decimal:2', 'ingredients' => 'array', 'metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
