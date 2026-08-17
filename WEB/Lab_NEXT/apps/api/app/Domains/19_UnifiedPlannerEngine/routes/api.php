<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('planner/tree', [\App\Domains\UnifiedPlannerEngine\Http\Controllers\PlannerItemController::class, 'tree']);
    Route::get('planner/agenda', [\App\Domains\UnifiedPlannerEngine\Http\Controllers\PlannerItemController::class, 'agenda']);

    Route::apiResource('planner/items', \App\Domains\UnifiedPlannerEngine\Http\Controllers\PlannerItemController::class)
        ->parameter('items', 'item');

    Route::post('planner/items/{item}/toggle', [\App\Domains\UnifiedPlannerEngine\Http\Controllers\PlannerItemController::class, 'toggle']);
});