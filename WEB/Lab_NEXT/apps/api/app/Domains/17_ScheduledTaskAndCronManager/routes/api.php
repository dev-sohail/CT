<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('cron/validate', [\App\Domains\ScheduledTaskAndCronManager\Http\Controllers\ScheduledTaskController::class, 'validateExpression']);

    Route::apiResource('cron/tasks', \App\Domains\ScheduledTaskAndCronManager\Http\Controllers\ScheduledTaskController::class)
        ->parameter('tasks', 'task');

    Route::post('cron/tasks/{task}/run', [\App\Domains\ScheduledTaskAndCronManager\Http\Controllers\ScheduledTaskController::class, 'run']);
    Route::get('cron/tasks/{task}/logs', [\App\Domains\ScheduledTaskAndCronManager\Http\Controllers\ScheduledTaskController::class, 'logs']);
});