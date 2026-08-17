<?php

declare(strict_types=1);

namespace Ctlab\Support\Exceptions;

use Exception;

/**
 * Base exception for domain module errors.
 *
 * Domain exceptions carry a machine-readable code and a human-readable
 * message. They are caught by the global exception handler and rendered
 * as standard API error responses.
 */
class DomainException extends Exception
{
    /**
     * Create a domain exception with a message and optional code.
     */
    public static function of(string $message, int $code = 400): static
    {
        return new static($message, $code);
    }

    /**
     * Create a "not found" exception.
     */
    public static function notFound(string $resource = 'Resource', int|string $id = ''): static
    {
        $suffix = $id !== '' ? " #{$id}" : '';

        return static::of("{$resource}{$suffix} not found.", 404);
    }

    /**
     * Create a "forbidden" exception.
     */
    public static function forbidden(string $message = 'Forbidden.'): static
    {
        return static::of($message, 403);
    }

    /**
     * Create a "validation failed" exception.
     */
    public static function validationFailed(string $message = 'Validation failed.'): static
    {
        return static::of($message, 422);
    }
}
