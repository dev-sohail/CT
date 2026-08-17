<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('rules/capabilities', [\App\Domains\RuleEngineAndWorkflowBuilder\Http\Controllers\RuleController::class, 'capabilities']);

    Route::apiResource('rules', \App\Domains\RuleEngineAndWorkflowBuilder\Http\Controllers\RuleController::class)
        ->parameter('rules', 'rule');

    Route::post('rules/{rule}/test', [\App\Domains\RuleEngineAndWorkflowBuilder\Http\Controllers\RuleController::class, 'test']);
    Route::post('rules/{rule}/run', [\App\Domains\RuleEngineAndWorkflowBuilder\Http\Controllers\RuleController::class, 'run']);
    Route::get('rules/{rule}/logs', [\App\Domains\RuleEngineAndWorkflowBuilder\Http\Controllers\RuleController::class, 'logs']);
});