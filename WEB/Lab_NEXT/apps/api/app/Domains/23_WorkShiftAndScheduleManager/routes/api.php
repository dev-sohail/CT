<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('shifts/check-conflict', [\App\Domains\WorkShiftAndScheduleManager\Http\Controllers\ShiftController::class, 'check']);
    Route::get('shifts/monthly', [\App\Domains\WorkShiftAndScheduleManager\Http\Controllers\ShiftController::class, 'monthly']);

    Route::apiResource('shifts', \App\Domains\WorkShiftAndScheduleManager\Http\Controllers\ShiftController::class)
        ->parameter('shifts', 'shift');

    Route::get('shifts/{shift}/conflicts', [\App\Domains\WorkShiftAndScheduleManager\Http\Controllers\ShiftController::class, 'conflicts']);
});