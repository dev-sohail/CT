<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('papers', \App\Domains\ResearchAndPDFAnnotationManager\Http\Controllers\PaperController::class)
        ->parameter('papers', 'paper');

    Route::get('papers/{paper}/annotations', [\App\Domains\ResearchAndPDFAnnotationManager\Http\Controllers\PaperController::class, 'annotations']);
    Route::post('papers/{paper}/annotations', [\App\Domains\ResearchAndPDFAnnotationManager\Http\Controllers\PaperController::class, 'addAnnotation']);
    Route::put('papers/{paper}/annotations/{annotation}', [\App\Domains\ResearchAndPDFAnnotationManager\Http\Controllers\PaperController::class, 'updateAnnotation']);
    Route::delete('papers/{paper}/annotations/{annotation}', [\App\Domains\ResearchAndPDFAnnotationManager\Http\Controllers\PaperController::class, 'deleteAnnotation']);
});