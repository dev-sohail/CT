<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('sleep/today', [\App\Domains\SleepAndRecoveryTracker\Http\Controllers\SleepLogController::class, 'today']);
    Route::get('sleep/stats', [\App\Domains\SleepAndRecoveryTracker\Http\Controllers\SleepLogController::class, 'stats']);
    Route::apiResource('sleep', \App\Domains\SleepAndRecoveryTracker\Http\Controllers\SleepLogController::class)->parameter('sleep', 'sleepLog');
});
