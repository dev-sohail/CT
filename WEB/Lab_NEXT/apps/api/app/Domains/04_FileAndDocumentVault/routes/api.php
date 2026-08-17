<?php

use App\Domains\FileAndDocumentVault\Http\Controllers\DocumentController;
use App\Domains\FileAndDocumentVault\Http\Controllers\DocumentVersionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('documents/{documentId}/restore', [DocumentController::class, 'restore'])->name('documents.restore');

    Route::get('documents/{document}/versions', [DocumentVersionController::class, 'index'])->name('documents.versions');
    Route::post('documents/{document}/versions', [DocumentVersionController::class, 'store'])->name('documents.versions.store');
});
