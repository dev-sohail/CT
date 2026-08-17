<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('decisions/due-reviews', [\App\Domains\DecisionJournal\Http\Controllers\DecisionController::class, 'dueReviews']);
    Route::get('decisions/stats', [\App\Domains\DecisionJournal\Http\Controllers\DecisionController::class, 'stats']);
    Route::apiResource('decisions', \App\Domains\DecisionJournal\Http\Controllers\DecisionController::class)->parameter('decisions', 'decision');
});
