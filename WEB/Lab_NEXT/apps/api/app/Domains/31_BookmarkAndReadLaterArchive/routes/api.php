<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('bookmarks/stats', [\App\Domains\BookmarkAndReadLaterArchive\Http\Controllers\BookmarkController::class, 'stats']);

    Route::apiResource('bookmarks', \App\Domains\BookmarkAndReadLaterArchive\Http\Controllers\BookmarkController::class)
        ->parameter('bookmarks', 'bookmark');
});