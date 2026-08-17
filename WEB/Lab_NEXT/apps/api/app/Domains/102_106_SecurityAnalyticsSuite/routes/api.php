<?php

use App\Domains\SecurityAnalyticsSuite\Http\Controllers\SecurityAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('security-analytics/{type}/summary', [SecurityAnalyticsController::class, 'summary']);
    Route::get('security-analytics/{type}', [SecurityAnalyticsController::class, 'index']);
    Route::post('security-analytics/{type}', [SecurityAnalyticsController::class, 'store']);
    Route::get('security-analytics/{type}/{securityAnalyticsRecord}', [SecurityAnalyticsController::class, 'show']);
    Route::put('security-analytics/{type}/{securityAnalyticsRecord}', [SecurityAnalyticsController::class, 'update']);
    Route::delete('security-analytics/{type}/{securityAnalyticsRecord}', [SecurityAnalyticsController::class, 'destroy']);
});
