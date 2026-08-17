<?php

use App\Domains\DevOpsSuite\Http\Controllers\DevOpsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('devops/{type}/stats', [DevOpsController::class, 'stats']);
    Route::get('devops/{type}', [DevOpsController::class, 'index']);
    Route::post('devops/{type}', [DevOpsController::class, 'store']);
    Route::get('devops/{type}/{devOpsRecord}', [DevOpsController::class, 'show']);
    Route::put('devops/{type}/{devOpsRecord}', [DevOpsController::class, 'update']);
    Route::delete('devops/{type}/{devOpsRecord}', [DevOpsController::class, 'destroy']);
});
