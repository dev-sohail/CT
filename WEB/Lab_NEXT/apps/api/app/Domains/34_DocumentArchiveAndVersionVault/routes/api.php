<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('document-archive/stats', [\App\Domains\DocumentArchiveAndVersionVault\Http\Controllers\DocumentArchiveController::class, 'stats']);

    Route::apiResource('document-archive', \App\Domains\DocumentArchiveAndVersionVault\Http\Controllers\DocumentArchiveController::class)
        ->parameter('document-archive', 'archive')
        ->names('document-archive');
});