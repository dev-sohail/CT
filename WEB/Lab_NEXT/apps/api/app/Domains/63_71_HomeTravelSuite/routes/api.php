<?php

use App\Domains\HomeTravelSuite\Http\Controllers\HomeTravelController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('home-travel/{type}/summary', [HomeTravelController::class, 'summary']);
    Route::get('home-travel/{type}', [HomeTravelController::class, 'index']);
    Route::post('home-travel/{type}', [HomeTravelController::class, 'store']);
    Route::get('home-travel/{type}/{homeTravelRecord}', [HomeTravelController::class, 'show']);
    Route::put('home-travel/{type}/{homeTravelRecord}', [HomeTravelController::class, 'update']);
    Route::delete('home-travel/{type}/{homeTravelRecord}', [HomeTravelController::class, 'destroy']);
});
