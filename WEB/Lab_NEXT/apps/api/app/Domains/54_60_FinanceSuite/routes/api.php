<?php

use App\Domains\FinanceSuite\Http\Controllers\FinanceRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('finance/{type}/summary', [FinanceRecordController::class, 'summary']);
    Route::get('finance/{type}', [FinanceRecordController::class, 'index']);
    Route::post('finance/{type}', [FinanceRecordController::class, 'store']);
    Route::get('finance/{type}/{financeRecord}', [FinanceRecordController::class, 'show']);
    Route::put('finance/{type}/{financeRecord}', [FinanceRecordController::class, 'update']);
    Route::delete('finance/{type}/{financeRecord}', [FinanceRecordController::class, 'destroy']);
});
