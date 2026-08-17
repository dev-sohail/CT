<?php

namespace App\Domains\NutritionAndMealPlanner\Http\Controllers;

use App\Domains\NutritionAndMealPlanner\Http\Resources\MealResource;
use App\Domains\NutritionAndMealPlanner\Models\Meal;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class MealController extends ApiController
{
    public function index(Request $request)
    {
        $items = Meal::forUser($request->user()->id)->when($request->input('from'), fn ($q, $date) => $q->whereDate('eaten_on', '>=', $date))->when($request->input('to'), fn ($q, $date) => $q->whereDate('eaten_on', '<=', $date))->when($request->input('meal_type'), fn ($q, $type) => $q->where('meal_type', $type))->orderByDesc('eaten_on')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, MealResource::class);
    }

    public function store(Request $request)
    {
        $meal = Meal::create(array_merge(['user_id' => $request->user()->id], $this->validateMeal($request)));
        return $this->respondCreated(MealResource::make($meal)->toArray($request));
    }

    public function show(Request $request, Meal $meal)
    {
        $this->authorizeOwner($request, $meal);
        return $this->respondSuccess(MealResource::make($meal)->toArray($request));
    }

    public function update(Request $request, Meal $meal)
    {
        $this->authorizeOwner($request, $meal);
        $meal->update($this->validateMeal($request, true));
        return $this->respondSuccess(MealResource::make($meal)->toArray($request));
    }

    public function destroy(Request $request, Meal $meal)
    {
        $this->authorizeOwner($request, $meal);
        $meal->delete();
        return $this->respondNoContent();
    }

    public function daily(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $items = Meal::forUser($request->user()->id)->whereDate('eaten_on', $date)->get();
        return $this->respondSuccess(['date' => $date, 'meals' => MealResource::collection($items), 'totals' => ['calories' => (float) $items->sum('calories'), 'protein_grams' => (float) $items->sum('protein_grams'), 'carbs_grams' => (float) $items->sum('carbs_grams'), 'fat_grams' => (float) $items->sum('fat_grams')]]);
    }

    public function stats(Request $request)
    {
        $items = Meal::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total_meals' => $items->count(), 'days_logged' => $items->pluck('eaten_on')->unique()->count(), 'total_calories' => (float) $items->sum('calories'), 'average_daily_calories' => $items->pluck('eaten_on')->unique()->isEmpty() ? 0 : round((float) $items->sum('calories') / $items->pluck('eaten_on')->unique()->count(), 2), 'by_meal_type' => $items->groupBy('meal_type')->map->count()]);
    }

    private function validateMeal(Request $request, bool $partial = false): array
    {
        return $request->validate(['eaten_on' => [$partial ? 'sometimes' : 'required', 'date'], 'meal_type' => ['sometimes', 'in:breakfast,lunch,dinner,snack,other'], 'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'calories' => ['nullable', 'numeric', 'min:0'], 'protein_grams' => ['nullable', 'numeric', 'min:0'], 'carbs_grams' => ['nullable', 'numeric', 'min:0'], 'fat_grams' => ['nullable', 'numeric', 'min:0'], 'ingredients' => ['nullable', 'array'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, Meal $meal): void
    {
        if ($meal->user_id !== $request->user()->id) abort(404);
    }
}
