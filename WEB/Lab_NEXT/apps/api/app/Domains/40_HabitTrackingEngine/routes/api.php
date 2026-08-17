<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('habits/today', [\App\Domains\HabitTrackingEngine\Http\Controllers\HabitController::class, 'today']);
    Route::get('habits/stats', [\App\Domains\HabitTrackingEngine\Http\Controllers\HabitController::class, 'stats']);
    Route::post('habits/{habit}/log', [\App\Domains\HabitTrackingEngine\Http\Controllers\HabitController::class, 'log']);
    Route::apiResource('habits', \App\Domains\HabitTrackingEngine\Http\Controllers\HabitController::class)
        ->parameter('habits', 'habit');
});
