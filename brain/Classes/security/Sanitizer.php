<?php

declare(strict_types=1);

class Sanitizer
{
    public function string(?string $value): string
    {
        return trim(strip_tags((string)$value));
    }

    public function html(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function int(mixed $value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public function float(mixed $value): float
    {
        $clean = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
        return (float)$clean;
    }

    public function email(?string $value): string
    {
        return (string)filter_var((string)$value, FILTER_SANITIZE_EMAIL);
    }

    public function url(?string $value): string
    {
        return (string)filter_var((string)$value, FILTER_SANITIZE_URL);
    }

    public function filename(?string $value): string
    {
        $value = (string)$value;
        $value = str_replace(['/', '\\', '..'], '', $value);
        $value = preg_replace('/[^a-zA-Z0-9._-]/', '_', $value);
        return substr($value, 0, 255);
    }

    public function path(?string $value): string
    {
        $value = (string)$value;
        $value = str_replace(['../', '..\\'], '', $value);
        return $value;
    }

    public function array(array $data, bool $recursive = true): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value) && $recursive) {
                $sanitized[$key] = $this->array($value, $recursive);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->string($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    public function alphanumeric(?string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', (string)$value);
    }

    public function alpha(?string $value): string
    {
        return preg_replace('/[^a-zA-Z]/', '', (string)$value);
    }

    public function numeric(?string $value): string
    {
        return preg_replace('/[^0-9]/', '', (string)$value);
    }

    public function slug(?string $value, string $separator = '-'): string
    {
        $value = strtolower((string)$value);
        $value = preg_replace('/[^a-z0-9]+/', $separator, $value);
        $value = trim($value, $separator);
        return $value;
    }

    public function stripWhitespace(?string $value): string
    {
        return preg_replace('/\s+/', ' ', trim((string)$value));
    }

    public function stripScripts(?string $value): string
    {
        return preg_replace('#<script(.*?)>(.*?)</script>#is', '', (string)$value);
    }

    public function json(mixed $value): ?string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded);
            }
        }
        return null;
    }

    public static function clean(mixed $value, string $type = 'string'): mixed
    {
        $instance = new self();
        
        return match($type) {
            'string' => $instance->string($value),
            'html' => $instance->html($value),
            'int', 'integer' => $instance->int($value),
            'float', 'double' => $instance->float($value),
            'email' => $instance->email($value),
            'url' => $instance->url($value),
            'array' => $instance->array((array)$value),
            default => $value,
        };
    }
}
