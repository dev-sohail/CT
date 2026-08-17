<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('courses/stats', [\App\Domains\StudyAndCourseTracker\Http\Controllers\CourseController::class, 'stats']);

    Route::apiResource('courses', \App\Domains\StudyAndCourseTracker\Http\Controllers\CourseController::class)
        ->parameter('courses', 'course');

    Route::post('courses/{course}/log-hours', [\App\Domains\StudyAndCourseTracker\Http\Controllers\CourseController::class, 'logHours']);
});