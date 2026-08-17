<?php

namespace App\Domains\SharedRestApiGateway\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Ctlab\Support\Foundation\ApiResponse;

class ApiController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected function respondSuccess(mixed $data = null, array $meta = [])
    {
        return ApiResponse::success($data, $meta);
    }

    protected function respondCreated(mixed $data = null, array $meta = [])
    {
        return ApiResponse::created($data, $meta);
    }

    protected function respondNoContent()
    {
        return ApiResponse::noContent();
    }

    protected function respondError(string $message, int $code = 400, mixed $errors = null, array $meta = [])
    {
        return ApiResponse::error($message, $code, $errors, $meta);
    }

    protected function respondPaginated(LengthAwarePaginator $paginator, ?string $resourceClass = null, array $meta = [])
    {
        return ApiResponse::paginated($paginator, $resourceClass, $meta);
    }
}
