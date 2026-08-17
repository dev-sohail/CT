<?php

namespace App\Domains\NutritionAndMealPlanner\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'eaten_on' => $this->eaten_on?->toDateString(), 'meal_type' => $this->meal_type, 'name' => $this->name, 'calories' => $this->calories !== null ? (float) $this->calories : null, 'protein_grams' => $this->protein_grams !== null ? (float) $this->protein_grams : null, 'carbs_grams' => $this->carbs_grams !== null ? (float) $this->carbs_grams : null, 'fat_grams' => $this->fat_grams !== null ? (float) $this->fat_grams : null, 'ingredients' => $this->ingredients, 'notes' => $this->notes, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
