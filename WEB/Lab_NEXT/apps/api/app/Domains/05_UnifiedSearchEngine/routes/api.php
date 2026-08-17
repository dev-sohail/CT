<?php

use App\Domains\UnifiedSearchEngine\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
});
