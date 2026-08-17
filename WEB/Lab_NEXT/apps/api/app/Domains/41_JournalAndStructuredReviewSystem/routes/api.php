<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('journal/calendar', [\App\Domains\JournalAndStructuredReviewSystem\Http\Controllers\JournalEntryController::class, 'calendar']);
    Route::get('journal/stats', [\App\Domains\JournalAndStructuredReviewSystem\Http\Controllers\JournalEntryController::class, 'stats']);
    Route::apiResource('journal', \App\Domains\JournalAndStructuredReviewSystem\Http\Controllers\JournalEntryController::class)
        ->parameter('journal', 'journalEntry');
});
