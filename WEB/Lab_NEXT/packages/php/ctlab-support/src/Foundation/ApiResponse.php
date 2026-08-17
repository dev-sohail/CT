<?php

namespace Ctlab\Support\Foundation;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    public const DEFAULT_VERSION = 'v1';

    public static function success(mixed $data = null, array $meta = []): JsonResponse
    {
        return new JsonResponse(self::envelope($data, self::mergeMeta($meta), null), 200);
    }

    public static function created(mixed $data = null, array $meta = []): JsonResponse
    {
        return new JsonResponse(self::envelope($data, self::mergeMeta($meta), null), 201);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    public static function error(string $message, int $code = 400, mixed $errors = null, array $meta = []): JsonResponse
    {
        $payload = self::envelope(
            null,
            self::mergeMeta($meta),
            [
                'code' => $code,
                'message' => $message,
                'details' => $errors,
            ]
        );

        return new JsonResponse($payload, $code);
    }

    public static function paginated(LengthAwarePaginator $paginator, ?string $resourceClass = null, array $meta = []): JsonResponse
    {
        $items = $paginator->items();

        if ($resourceClass !== null && is_subclass_of($resourceClass, JsonResource::class)) {
            $items = $resourceClass::collection($paginator->getCollection())->resolve();
        }

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];

        $merged = array_merge($meta, ['pagination' => $pagination]);

        return new JsonResponse(self::envelope($items, self::mergeMeta($merged), null), 200);
    }

    protected static function mergeMeta(array $meta): array
    {
        return array_merge([
            'version' => self::DEFAULT_VERSION,
            'timestamp' => now()->toIso8601String(),
        ], $meta);
    }

    protected static function envelope(mixed $data, array $meta, ?array $errors): array
    {
        return [
            'data' => $data,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }
}
