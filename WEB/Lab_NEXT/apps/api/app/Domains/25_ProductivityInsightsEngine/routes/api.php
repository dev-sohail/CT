<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('insights/overview', [\App\Domains\ProductivityInsightsEngine\Http\Controllers\InsightsController::class, 'overview']);
    Route::get('insights/completion', [\App\Domains\ProductivityInsightsEngine\Http\Controllers\InsightsController::class, 'completion']);
    Route::get('insights/trends', [\App\Domains\ProductivityInsightsEngine\Http\Controllers\InsightsController::class, 'trends']);
    Route::get('insights/focus', [\App\Domains\ProductivityInsightsEngine\Http\Controllers\InsightsController::class, 'focus']);
});