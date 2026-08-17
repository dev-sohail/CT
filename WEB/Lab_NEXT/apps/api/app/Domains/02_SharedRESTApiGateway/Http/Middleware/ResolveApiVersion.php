<?php

namespace App\Domains\SharedRestApiGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ctlab\Support\Foundation\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiVersion
{
    public const SUPPORTED = ['v1'];

    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->segment(2) ?? 'v1';

        $request->attributes->set('api_version', $version);

        if (! in_array($version, self::SUPPORTED, true)) {
            return ApiResponse::error("Unsupported API version: {$version}", 406);
        }

        return $next($request);
    }
}
