<?php

declare(strict_types=1);

namespace Ctlab\Support\Foundation;

use Exception;

/**
 * Base exception for domain module errors.
 *
 * Carries a machine-readable HTTP code and a human-readable message.
 */
class DomainException extends Exception
{
    public static function of(string $message, int $code = 400): static
    {
        return new static($message, $code);
    }

    public static function notFound(string $resource = 'Resource', int|string $id = ''): static
    {
        $suffix = $id !== '' ? " #{$id}" : '';

        return static::of("{$resource}{$suffix} not found.", 404);
    }

    public static function forbidden(string $message = 'Forbidden.'): static
    {
        return static::of($message, 403);
    }

    public static function validationFailed(string $message = 'Validation failed.'): static
    {
        return static::of($message, 422);
    }
}