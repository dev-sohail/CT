<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('scorecard/summary', [\App\Domains\PersonalScorecardLifeKPITracker\Http\Controllers\ScorecardMetricController::class, 'summary']);
    Route::post('scorecard/{metric}/measure', [\App\Domains\PersonalScorecardLifeKPITracker\Http\Controllers\ScorecardMetricController::class, 'measure']);
    Route::apiResource('scorecard', \App\Domains\PersonalScorecardLifeKPITracker\Http\Controllers\ScorecardMetricController::class)->parameter('scorecard', 'metric');
});
