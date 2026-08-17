<?php

use App\Domains\TaggingAndCategorizationEngine\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('tags/attach', [TagController::class, 'attach'])->name('tags.attach');
    Route::post('tags/detach', [TagController::class, 'detach'])->name('tags.detach');
});
