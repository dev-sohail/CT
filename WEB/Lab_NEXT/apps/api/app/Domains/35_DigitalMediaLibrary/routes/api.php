<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('media/stats', [\App\Domains\DigitalMediaLibrary\Http\Controllers\MediaItemController::class, 'stats']);

    Route::apiResource('media', \App\Domains\DigitalMediaLibrary\Http\Controllers\MediaItemController::class)
        ->parameter('media', 'mediaItem')
        ->names('media');
});