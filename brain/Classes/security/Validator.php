<?php

declare(strict_types=1);

class Validator
{
    protected array $errors = [];
    protected array $data = [];

    public function validate(array $data, array $rules): bool
    {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            
            foreach ($ruleList as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    protected function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;
        [$ruleName, $params] = $this->parseRule($rule);

        $method = 'validate' . ucfirst($ruleName);
        if (method_exists($this, $method)) {
            if (!$this->$method($value, $params)) {
                $this->errors[$field][] = $this->getErrorMessage($field, $ruleName, $params);
            }
        }
    }

    protected function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, explode(',', $param)];
        }
        return [$rule, []];
    }

    public function validateRequired($value): bool
    {
        return !empty($value) || $value === '0' || $value === 0;
    }

    public function validateEmail(?string $value): bool
    {
        return $value && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validateUrl(?string $value): bool
    {
        return $value && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function validateMin(?string $value, array $params): bool
    {
        $min = (int)($params[0] ?? 0);
        return mb_strlen((string)$value, 'UTF-8') >= $min;
    }

    public function validateMax(?string $value, array $params): bool
    {
        $max = (int)($params[0] ?? 0);
        return mb_strlen((string)$value, 'UTF-8') <= $max;
    }

    public function validateNumeric($value): bool
    {
        return is_numeric($value);
    }

    public function validateInteger($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function validateAlpha(?string $value): bool
    {
        return $value && preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }

    public function validateAlphaNum(?string $value): bool
    {
        return $value && preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    public function validateRegex(?string $value, array $params): bool
    {
        $pattern = $params[0] ?? '';
        return $value && $pattern && preg_match($pattern, $value) === 1;
    }

    public function validateIn($value, array $params): bool
    {
        return in_array($value, $params, true);
    }

    public function validateDate(?string $value): bool
    {
        return $value && strtotime($value) !== false;
    }

    protected function getErrorMessage(string $field, string $rule, array $params): string
    {
        $messages = [
            'required' => "$field is required",
            'email' => "$field must be a valid email",
            'url' => "$field must be a valid URL",
            'min' => "$field must be at least {$params[0]} characters",
            'max' => "$field must not exceed {$params[0]} characters",
            'numeric' => "$field must be numeric",
            'integer' => "$field must be an integer",
            'alpha' => "$field must contain only letters",
            'alphaNum' => "$field must contain only letters and numbers",
            'in' => "$field must be one of: " . implode(', ', $params),
            'date' => "$field must be a valid date",
        ];

        return $messages[$rule] ?? "$field is invalid";
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function failed(): bool
    {
        return !empty($this->errors);
    }
}
