<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('timeline/milestones', [\App\Domains\LifeTimelineAndHistoryArchive\Http\Controllers\TimelineEventController::class, 'milestones']);
    Route::get('timeline/stats', [\App\Domains\LifeTimelineAndHistoryArchive\Http\Controllers\TimelineEventController::class, 'stats']);
    Route::apiResource('timeline', \App\Domains\LifeTimelineAndHistoryArchive\Http\Controllers\TimelineEventController::class)->parameter('timeline', 'timelineEvent');
});
