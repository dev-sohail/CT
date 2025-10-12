<?php

declare(strict_types=1);

/**
 * Validation Class
 * 
 * Provides validation utilities for various data types
 */
class Validation
{
    private array $errors = [];
    private array $rules = [];

    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool True if valid
     */
    public function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid
     */
    public function url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate IP address
     * 
     * @param string $ip IP to validate
     * @return bool True if valid
     */
    public function ip(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate integer
     * 
     * @param mixed $value Value to validate
     * @param int|null $min Minimum value
     * @param int|null $max Maximum value
     * @return bool True if valid
     */
    public function integer(mixed $value, ?int $min = null, ?int $max = null): bool
    {
        if (!is_numeric($value) || !is_int($value + 0)) {
            return false;
        }

        $intValue = (int) $value;

        if ($min !== null && $intValue < $min) {
            return false;
        }

        if ($max !== null && $intValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate float
     * 
     * @param mixed $value Value to validate
     * @param float|null $min Minimum value
     * @param float|null $max Maximum value
     * @return bool True if valid
     */
    public function float(mixed $value, ?float $min = null, ?float $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $floatValue = (float) $value;

        if ($min !== null && $floatValue < $min) {
            return false;
        }

        if ($max !== null && $floatValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate string length
     * 
     * @param string $value Value to validate
     * @param int|null $min Minimum length
     * @param int|null $max Maximum length
     * @return bool True if valid
     */
    public function stringLength(string $value, ?int $min = null, ?int $max = null): bool
    {
        $length = strlen($value);

        if ($min !== null && $length < $min) {
            return false;
        }

        if ($max !== null && $length > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate required field
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid
     */
    public function required(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null && $value !== '';
    }

    /**
     * Validate alphanumeric
     * 
     * @param string $value Value to validate
     * @return bool True if valid
     */
    public function alphanumeric(string $value): bool
    {
        return ctype_alnum($value);
    }

    /**
     * Validate alphabetic
     * 
     * @param string $value Value to validate
     * @return bool True if valid
     */
    public function alphabetic(string $value): bool
    {
        return ctype_alpha($value);
    }

    /**
     * Validate numeric
     * 
     * @param string $value Value to validate
     * @return bool True if valid
     */
    public function numeric(string $value): bool
    {
        return ctype_digit($value);
    }

    /**
     * Validate phone number (basic)
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid
     */
    public function phone(string $phone): bool
    {
        $cleaned = preg_replace('/[^\d]/', '', $phone);
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }

    /**
     * Validate date
     * 
     * @param string $date Date to validate
     * @param string $format Date format
     * @return bool True if valid
     */
    public function date(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate date range
     * 
     * @param string $date Date to validate
     * @param string|null $minDate Minimum date
     * @param string|null $maxDate Maximum date
     * @param string $format Date format
     * @return bool True if valid
     */
    public function dateRange(string $date, ?string $minDate = null, ?string $maxDate = null, string $format = 'Y-m-d'): bool
    {
        if (!$this->date($date, $format)) {
            return false;
        }

        $dateObj = DateTime::createFromFormat($format, $date);

        if ($minDate !== null) {
            $minObj = DateTime::createFromFormat($format, $minDate);
            if ($minObj && $dateObj < $minObj) {
                return false;
            }
        }

        if ($maxDate !== null) {
            $maxObj = DateTime::createFromFormat($format, $maxDate);
            if ($maxObj && $dateObj > $maxObj) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate against regex pattern
     * 
     * @param string $value Value to validate
     * @param string $pattern Regex pattern
     * @return bool True if valid
     */
    public function regex(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate in array
     * 
     * @param mixed $value Value to validate
     * @param array $array Array to check against
     * @return bool True if valid
     */
    public function inArray(mixed $value, array $array): bool
    {
        return in_array($value, $array, true);
    }

    /**
     * Validate not in array
     * 
     * @param mixed $value Value to validate
     * @param array $array Array to check against
     * @return bool True if valid
     */
    public function notInArray(mixed $value, array $array): bool
    {
        return !in_array($value, $array, true);
    }

    /**
     * Validate array
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid
     */
    public function array(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Validate object
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid
     */
    public function object(mixed $value): bool
    {
        return is_object($value);
    }

    /**
     * Validate boolean
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid
     */
    public function boolean(mixed $value): bool
    {
        return is_bool($value);
    }

    /**
     * Validate file upload
     * 
     * @param array $file File array from $_FILES
     * @param array $allowedTypes Allowed MIME types
     * @param int|null $maxSize Maximum file size in bytes
     * @return bool True if valid
     */
    public function file(array $file, array $allowedTypes = [], ?int $maxSize = null): bool
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if ($maxSize !== null && $file['size'] > $maxSize) {
            return false;
        }

        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if all validations pass
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        $this->rules = $rules;

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules);
        }

        return empty($this->errors);
    }

    /**
     * Validate a single field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rules Field rules
     */
    private function validateField(string $field, mixed $value, array $rules): void
    {
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $this->applyStringRule($field, $value, $rule);
            } elseif (is_array($rule)) {
                $this->applyArrayRule($field, $value, $rule);
            }
        }
    }

    /**
     * Apply string rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule string
     */
    private function applyStringRule(string $field, mixed $value, string $rule): void
    {
        switch ($rule) {
            case 'required':
                if (!$this->required($value)) {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;
            case 'email':
                if (!$this->email((string) $value)) {
                    $this->addError($field, "The {$field} field must be a valid email address.");
                }
                break;
            case 'url':
                if (!$this->url((string) $value)) {
                    $this->addError($field, "The {$field} field must be a valid URL.");
                }
                break;
            case 'integer':
                if (!$this->integer($value)) {
                    $this->addError($field, "The {$field} field must be an integer.");
                }
                break;
            case 'float':
                if (!$this->float($value)) {
                    $this->addError($field, "The {$field} field must be a float.");
                }
                break;
            case 'array':
                if (!$this->array($value)) {
                    $this->addError($field, "The {$field} field must be an array.");
                }
                break;
            case 'boolean':
                if (!$this->boolean($value)) {
                    $this->addError($field, "The {$field} field must be a boolean.");
                }
                break;
        }
    }

    /**
     * Apply array rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rule Rule array
     */
    private function applyArrayRule(string $field, mixed $value, array $rule): void
    {
        $ruleName = array_key_first($rule);
        $ruleValue = $rule[$ruleName];

        switch ($ruleName) {
            case 'min':
                if (is_numeric($value) && !$this->integer($value, $ruleValue)) {
                    $this->addError($field, "The {$field} field must be at least {$ruleValue}.");
                } elseif (is_string($value) && !$this->stringLength($value, $ruleValue)) {
                    $this->addError($field, "The {$field} field must be at least {$ruleValue} characters.");
                }
                break;
            case 'max':
                if (is_numeric($value) && !$this->integer($value, null, $ruleValue)) {
                    $this->addError($field, "The {$field} field must not exceed {$ruleValue}.");
                } elseif (is_string($value) && !$this->stringLength($value, null, $ruleValue)) {
                    $this->addError($field, "The {$field} field must not exceed {$ruleValue} characters.");
                }
                break;
            case 'in':
                if (!$this->inArray($value, $ruleValue)) {
                    $this->addError($field, "The {$field} field must be one of: " . implode(', ', $ruleValue) . ".");
                }
                break;
            case 'not_in':
                if (!$this->notInArray($value, $ruleValue)) {
                    $this->addError($field, "The {$field} field must not be one of: " . implode(', ', $ruleValue) . ".");
                }
                break;
            case 'regex':
                if (!$this->regex((string) $value, $ruleValue)) {
                    $this->addError($field, "The {$field} field format is invalid.");
                }
                break;
        }
    }

    /**
     * Add validation error
     * 
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     * 
     * @param string $field Field name
     * @return array Field errors
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if field has errors
     * 
     * @param string $field Field name
     * @return bool True if field has errors
     */
    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Get first error for a field
     * 
     * @param string $field Field name
     * @return string|null First error message or null
     */
    public function getFirstError(string $field): ?string
    {
        $errors = $this->getFieldErrors($field);
        return $errors[0] ?? null;
    }

    /**
     * Get all error messages as flat array
     * 
     * @return array All error messages
     */
    public function getAllErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }
}
