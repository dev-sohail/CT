<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('body-observations/trend', [\App\Domains\SymptomAndBodyMetricsTracker\Http\Controllers\BodyObservationController::class, 'trend']);
    Route::get('body-observations/stats', [\App\Domains\SymptomAndBodyMetricsTracker\Http\Controllers\BodyObservationController::class, 'stats']);
    Route::apiResource('body-observations', \App\Domains\SymptomAndBodyMetricsTracker\Http\Controllers\BodyObservationController::class)->parameter('body-observations', 'bodyObservation');
});
