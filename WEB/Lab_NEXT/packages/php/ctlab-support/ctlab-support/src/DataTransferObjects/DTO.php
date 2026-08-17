<?php

declare(strict_types=1);

namespace Ctlab\Support\DataTransferObjects;

/**
 * Base data transfer object for domain modules.
 *
 * DTOs are immutable value objects that carry data between layers.
 * They prevent domain logic from depending on request arrays or
 * Eloquent models directly.
 *
 * Extend this per use case:
 *
 *     final class RegisterUserData extends DTO
 *     {
 *         public function __construct(
 *             public readonly string $name,
 *             public readonly string $email,
 *             public readonly string $password,
 *         ) {}
 *     }
 *
 *     $dto = RegisterUserData::from(['name' => 'Alice', ...]);
 */
abstract class DTO
{
    /**
     * Create a DTO from an array of attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return static
     */
    public static function from(array $attributes): static
    {
        // If the subclass defines a constructor with typed properties,
        // we use reflection to map array keys to constructor parameters.
        $constructor = (new \ReflectionClass(static::class))->getConstructor();

        if ($constructor === null) {
            // No constructor — use the parent (unlikely for concrete DTOs).
            return new static();
        }

        $parameters = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $parameters[] = $attributes[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
        }

        return new static(...$parameters);
    }

    /**
     * Create a DTO from an object (typically an Eloquent model or Request).
     *
     * @param  array-key[]  $attributes  Attribute names to extract.
     */
    public static function fromModel(object $model, array $attributes = []): static
    {
        $data = [];

        foreach ($attributes as $attribute) {
            $data[$attribute] = $model->{$attribute};
        }

        // Fallback: if no attributes specified, use the model's toArray()
        if ($data === [] && method_exists($model, 'toArray')) {
            $data = $model->toArray();
        }

        return static::from($data);
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isInitialized($this)) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Convert the DTO to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
