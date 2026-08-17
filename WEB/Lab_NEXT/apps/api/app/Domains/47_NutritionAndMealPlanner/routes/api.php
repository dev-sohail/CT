<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('meals/daily', [\App\Domains\NutritionAndMealPlanner\Http\Controllers\MealController::class, 'daily']);
    Route::get('meals/stats', [\App\Domains\NutritionAndMealPlanner\Http\Controllers\MealController::class, 'stats']);
    Route::apiResource('meals', \App\Domains\NutritionAndMealPlanner\Http\Controllers\MealController::class)->parameter('meals', 'meal');
});
