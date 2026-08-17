<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('expenses/summary', [\App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Controllers\ExpenseController::class, 'summary']);
    Route::get('expenses/budget-summary', [\App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Controllers\ExpenseController::class, 'budgetSummary']);
    Route::post('expenses/budget', [\App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Controllers\ExpenseController::class, 'budget']);
    Route::apiResource('expenses', \App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Controllers\ExpenseController::class)->parameter('expenses', 'expense');
});
