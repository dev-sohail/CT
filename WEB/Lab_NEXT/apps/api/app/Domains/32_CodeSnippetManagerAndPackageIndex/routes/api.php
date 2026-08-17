<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('snippets/packages', [\App\Domains\CodeSnippetManagerAndPackageIndex\Http\Controllers\CodeSnippetController::class, 'packageIndex']);
    Route::get('snippets/stats', [\App\Domains\CodeSnippetManagerAndPackageIndex\Http\Controllers\CodeSnippetController::class, 'stats']);

    Route::apiResource('snippets', \App\Domains\CodeSnippetManagerAndPackageIndex\Http\Controllers\CodeSnippetController::class)
        ->parameter('snippets', 'snippet');

    Route::post('snippets/{snippet}/favorite', [\App\Domains\CodeSnippetManagerAndPackageIndex\Http\Controllers\CodeSnippetController::class, 'toggleFavorite']);
});