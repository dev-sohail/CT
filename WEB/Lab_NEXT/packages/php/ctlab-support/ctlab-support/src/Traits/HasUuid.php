<?php

declare(strict_types=1);

namespace Ctlab\Support\Traits;

use Illuminate\Support\Str;

/**
 * Adds UUID v4 generation to Eloquent models.
 *
 * Usage:
 *     class Order extends Model
 *     {
 *         use HasUuid;
 *     }
 *
 *     $order = Order::create([...]); // uuid is auto-generated
 *     $order->uuid; // '550e8400-e29b-41d4-a716-446655440000'
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
