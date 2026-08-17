<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('workouts/stats', [\App\Domains\WorkoutAndTrainingPlanner\Http\Controllers\WorkoutController::class, 'stats']);
    Route::post('workouts/{workout}/complete', [\App\Domains\WorkoutAndTrainingPlanner\Http\Controllers\WorkoutController::class, 'complete']);
    Route::apiResource('workouts', \App\Domains\WorkoutAndTrainingPlanner\Http\Controllers\WorkoutController::class)->parameter('workouts', 'workout');
});
