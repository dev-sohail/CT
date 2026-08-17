<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('medical/follow-ups', [\App\Domains\MedicalHistoryAndVisitLog\Http\Controllers\MedicalRecordController::class, 'followUps']);
    Route::get('medical/stats', [\App\Domains\MedicalHistoryAndVisitLog\Http\Controllers\MedicalRecordController::class, 'stats']);
    Route::apiResource('medical', \App\Domains\MedicalHistoryAndVisitLog\Http\Controllers\MedicalRecordController::class)->parameter('medical', 'medicalRecord');
});
