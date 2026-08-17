<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('medications/stats', [\App\Domains\MedicationAndPrescriptionTracker\Http\Controllers\MedicationController::class, 'stats']);
    Route::post('medications/{medication}/log', [\App\Domains\MedicationAndPrescriptionTracker\Http\Controllers\MedicationController::class, 'log']);
    Route::get('medications/{medication}/adherence', [\App\Domains\MedicationAndPrescriptionTracker\Http\Controllers\MedicationController::class, 'adherence']);
    Route::apiResource('medications', \App\Domains\MedicationAndPrescriptionTracker\Http\Controllers\MedicationController::class)->parameter('medications', 'medication');
});
