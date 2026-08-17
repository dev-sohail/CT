<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('assets/stats', [\App\Domains\QRAssetTaggingSystem\Http\Controllers\AssetController::class, 'stats']);
    Route::post('assets/scan', [\App\Domains\QRAssetTaggingSystem\Http\Controllers\AssetController::class, 'scan']);
    Route::get('assets/{asset}/qr', [\App\Domains\QRAssetTaggingSystem\Http\Controllers\AssetController::class, 'qr']);

    Route::apiResource('assets', \App\Domains\QRAssetTaggingSystem\Http\Controllers\AssetController::class)
        ->parameter('assets', 'asset')
        ->names('assets');
});