<?php

namespace Ctlab\Support;

abstract class DataTransferObject
{
    public static function fromArray(array $data): static
    {
        $instance = new static;

        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            }
        }

        return $instance;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
