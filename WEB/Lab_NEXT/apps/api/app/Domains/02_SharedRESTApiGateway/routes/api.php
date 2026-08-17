<?php

use App\Domains\SharedRestApiGateway\Http\Controllers\OpenApiController;
use App\Domains\SharedRestApiGateway\Support\ApiGateway;
use Illuminate\Support\Facades\Route;

Route::get('info', function () {
    return response()->json([
        'service' => 'ctlab-api',
        'current_version' => ApiGateway::CURRENT_VERSION,
        'supported_versions' => ApiGateway::SUPPORTED_VERSIONS,
        'envelope' => ApiGateway::envelopeShape(),
        'conventions' => [
            'namespacing' => '/api/{version}/...',
            'pagination' => 'page + per_page query params',
            'filtering' => '?filter[field]=value (per module)',
            'auth' => 'Bearer token via Laravel Sanctum',
        ],
    ]);
})->name('gateway.info');

Route::get('openapi.json', [OpenApiController::class, 'json'])->name('api.docs.json');
Route::get('docs', [OpenApiController::class, 'html'])->name('api.docs');
