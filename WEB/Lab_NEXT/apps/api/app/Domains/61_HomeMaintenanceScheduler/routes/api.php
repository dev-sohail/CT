<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('maintenance/stats', [\App\Domains\HomeMaintenanceScheduler\Http\Controllers\MaintenanceTaskController::class, 'stats']);
    Route::post('maintenance/{maintenanceTask}/complete', [\App\Domains\HomeMaintenanceScheduler\Http\Controllers\MaintenanceTaskController::class, 'complete']);
    Route::apiResource('maintenance', \App\Domains\HomeMaintenanceScheduler\Http\Controllers\MaintenanceTaskController::class)->parameter('maintenance', 'maintenanceTask');
});
