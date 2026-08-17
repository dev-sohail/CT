<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('wellness/hydration', [\App\Domains\HydrationAndMovementReminderEngine\Http\Controllers\HydrationMovementController::class, 'hydration']);
    Route::post('wellness/movement', [\App\Domains\HydrationAndMovementReminderEngine\Http\Controllers\HydrationMovementController::class, 'movement']);
    Route::get('wellness/today', [\App\Domains\HydrationAndMovementReminderEngine\Http\Controllers\HydrationMovementController::class, 'today']);
    Route::get('wellness/stats', [\App\Domains\HydrationAndMovementReminderEngine\Http\Controllers\HydrationMovementController::class, 'stats']);
});
