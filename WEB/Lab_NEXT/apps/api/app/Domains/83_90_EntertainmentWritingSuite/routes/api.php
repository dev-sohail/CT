<?php

use App\Domains\EntertainmentWritingSuite\Http\Controllers\EntertainmentWritingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('entertainment/{type}/stats', [EntertainmentWritingController::class, 'stats']);
    Route::get('entertainment/{type}', [EntertainmentWritingController::class, 'index']);
    Route::post('entertainment/{type}', [EntertainmentWritingController::class, 'store']);
    Route::get('entertainment/{type}/{entertainmentWritingRecord}', [EntertainmentWritingController::class, 'show']);
    Route::put('entertainment/{type}/{entertainmentWritingRecord}', [EntertainmentWritingController::class, 'update']);
    Route::delete('entertainment/{type}/{entertainmentWritingRecord}', [EntertainmentWritingController::class, 'destroy']);
});
