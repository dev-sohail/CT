<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('learning/overview', [\App\Domains\LearningAnalyticsDashboard\Http\Controllers\LearningAnalyticsController::class, 'overview']);
    Route::get('learning/streaks', [\App\Domains\LearningAnalyticsDashboard\Http\Controllers\LearningAnalyticsController::class, 'streaks']);
    Route::get('learning/retention', [\App\Domains\LearningAnalyticsDashboard\Http\Controllers\LearningAnalyticsController::class, 'retention']);
});