<?php

namespace App\Domains\SharedRestApiGateway\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Ctlab\Support\Foundation\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e): JsonResponse|Response
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return parent::render($request, $e);
        }

        return $this->renderApi($e);
    }

    protected function renderApi(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error('Resource not found', 404);
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::error($e->getMessage() ?: 'Forbidden', 403);
        }

        if ($e instanceof HttpException) {
            return ApiResponse::error($e->getMessage() ?: 'Error', $e->getStatusCode());
        }

        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        if (app()->environment('production') && $status >= 500) {
            return ApiResponse::error('Server error', 500);
        }

        return ApiResponse::error($e->getMessage() ?: 'Server error', $status >= 100 ? $status : 500);
    }
}
