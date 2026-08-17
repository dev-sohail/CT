<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('transfer/domains', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'domains']);

    Route::post('exports', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'startExport']);
    Route::get('exports', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'indexExports']);
    Route::get('exports/{export}', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'showExport']);
    Route::get('exports/{export}/download', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'downloadExport']);

    Route::post('imports', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'startImport']);
    Route::get('imports', [\App\Domains\ImportExportAndDataPortability\Http\Controllers\ImportExportController::class, 'indexImports']);
});