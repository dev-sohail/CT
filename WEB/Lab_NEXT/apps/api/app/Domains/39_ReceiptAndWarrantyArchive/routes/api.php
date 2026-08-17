<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('receipts/stats', [\App\Domains\ReceiptAndWarrantyArchive\Http\Controllers\ReceiptController::class, 'stats']);
    Route::get('receipts/warranties', [\App\Domains\ReceiptAndWarrantyArchive\Http\Controllers\ReceiptController::class, 'warranties']);

    Route::apiResource('receipts', \App\Domains\ReceiptAndWarrantyArchive\Http\Controllers\ReceiptController::class)
        ->parameter('receipts', 'receipt')
        ->names('receipts');
});