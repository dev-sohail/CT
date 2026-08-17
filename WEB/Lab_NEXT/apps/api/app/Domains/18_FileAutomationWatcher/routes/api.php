<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('file-watcher/rules', \App\Domains\FileAutomationWatcher\Http\Controllers\FileWatcherRuleController::class)
        ->parameter('rules', 'rule');

    Route::post('file-watcher/rules/{rule}/scan', [\App\Domains\FileAutomationWatcher\Http\Controllers\FileWatcherRuleController::class, 'scan']);
    Route::post('file-watcher/rules/{rule}/run', [\App\Domains\FileAutomationWatcher\Http\Controllers\FileWatcherRuleController::class, 'run']);
    Route::get('file-watcher/rules/{rule}/logs', [\App\Domains\FileAutomationWatcher\Http\Controllers\FileWatcherRuleController::class, 'logs']);
});