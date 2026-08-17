<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('certifications/expiring-soon', [\App\Domains\CertificationAndSkillRoadmap\Http\Controllers\CertificationController::class, 'expiringSoon']);
    Route::get('certifications/stats', [\App\Domains\CertificationAndSkillRoadmap\Http\Controllers\CertificationController::class, 'stats']);

    Route::apiResource('certifications', \App\Domains\CertificationAndSkillRoadmap\Http\Controllers\CertificationController::class)
        ->parameter('certifications', 'certification');
});