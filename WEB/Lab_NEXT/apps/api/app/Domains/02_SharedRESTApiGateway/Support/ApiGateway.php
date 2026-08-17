<?php

namespace App\Domains\SharedRestApiGateway\Support;

use Ctlab\Support\Foundation\ApiResponse;

class ApiGateway
{
    public const CURRENT_VERSION = 'v1';

    public const SUPPORTED_VERSIONS = ['v1'];

    public static function isSupported(string $version): bool
    {
        return in_array($version, self::SUPPORTED_VERSIONS, true);
    }

    public static function envelopeShape(): array
    {
        return [
            'data' => 'resource | null',
            'meta' => [
                'version' => self::CURRENT_VERSION,
                'timestamp' => 'ISO8601',
                'pagination' => 'optional paginator meta',
            ],
            'errors' => 'null | { code, message, details }',
        ];
    }

    public static function response(): ApiResponse
    {
        return new ApiResponse;
    }
}
