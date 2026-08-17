<?php

use App\Domains\CareerBusinessSuite\Http\Controllers\CareerBusinessController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('career/{type}/summary', [CareerBusinessController::class, 'summary']);
    Route::get('career/{type}', [CareerBusinessController::class, 'index']);
    Route::post('career/{type}', [CareerBusinessController::class, 'store']);
    Route::get('career/{type}/{careerBusinessRecord}', [CareerBusinessController::class, 'show']);
    Route::put('career/{type}/{careerBusinessRecord}', [CareerBusinessController::class, 'update']);
    Route::delete('career/{type}/{careerBusinessRecord}', [CareerBusinessController::class, 'destroy']);
});
