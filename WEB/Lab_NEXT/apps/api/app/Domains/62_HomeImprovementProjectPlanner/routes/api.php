<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('home-projects/stats', [\App\Domains\HomeImprovementProjectPlanner\Http\Controllers\HomeProjectController::class, 'stats']);
    Route::post('home-projects/{homeProject}/complete', [\App\Domains\HomeImprovementProjectPlanner\Http\Controllers\HomeProjectController::class, 'complete']);
    Route::apiResource('home-projects', \App\Domains\HomeImprovementProjectPlanner\Http\Controllers\HomeProjectController::class)->parameter('home-projects', 'homeProject');
});
