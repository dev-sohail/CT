<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('roadmap/timeline', [\App\Domains\ProjectRoadmapPlanner\Http\Controllers\ProjectController::class, 'timeline']);

    Route::apiResource('roadmap/projects', \App\Domains\ProjectRoadmapPlanner\Http\Controllers\ProjectController::class)
        ->parameter('projects', 'project');

    Route::get('roadmap/projects/{project}/milestones', [\App\Domains\ProjectRoadmapPlanner\Http\Controllers\ProjectController::class, 'milestones']);
    Route::post('roadmap/projects/{project}/milestones', [\App\Domains\ProjectRoadmapPlanner\Http\Controllers\ProjectController::class, 'addMilestone']);
    Route::put('roadmap/projects/{project}/milestones/{milestone}', [\App\Domains\ProjectRoadmapPlanner\Http\Controllers\ProjectController::class, 'updateMilestone']);
    Route::delete('roadmap/projects/{project}/milestones/{milestone}', [\App\Domains\ProjectRoadmapPlanner\Http\Controllers\ProjectController::class, 'deleteMilestone']);
});