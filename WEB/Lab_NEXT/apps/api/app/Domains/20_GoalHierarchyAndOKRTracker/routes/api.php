<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('goals/tree', [\App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers\GoalController::class, 'tree']);

    Route::apiResource('goals', \App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers\GoalController::class)
        ->parameter('goals', 'goal');

    Route::get('goals/{goal}/key-results', [\App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers\GoalController::class, 'keyResults']);
    Route::post('goals/{goal}/key-results', [\App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers\GoalController::class, 'addKeyResult']);
    Route::put('goals/{goal}/key-results/{keyResult}', [\App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers\GoalController::class, 'updateKeyResult']);
    Route::delete('goals/{goal}/key-results/{keyResult}', [\App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers\GoalController::class, 'deleteKeyResult']);
});