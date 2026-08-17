<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('vision/board', [\App\Domains\VisionBoardAndBucketListManager\Http\Controllers\VisionItemController::class, 'board']);
    Route::get('vision/stats', [\App\Domains\VisionBoardAndBucketListManager\Http\Controllers\VisionItemController::class, 'stats']);
    Route::post('vision/{visionItem}/complete', [\App\Domains\VisionBoardAndBucketListManager\Http\Controllers\VisionItemController::class, 'complete']);
    Route::apiResource('vision', \App\Domains\VisionBoardAndBucketListManager\Http\Controllers\VisionItemController::class)->parameter('vision', 'visionItem');
});
