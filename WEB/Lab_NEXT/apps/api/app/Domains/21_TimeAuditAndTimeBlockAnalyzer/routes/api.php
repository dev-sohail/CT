<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('time/analytics', [\App\Domains\TimeAuditAndTimeBlockAnalyzer\Http\Controllers\TimeEntryController::class, 'analytics']);

    Route::apiResource('time/entries', \App\Domains\TimeAuditAndTimeBlockAnalyzer\Http\Controllers\TimeEntryController::class)
        ->parameter('entries', 'entry');
});