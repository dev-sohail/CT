<?php

declare(strict_types=1);

/**
 * Class FormBuilder
 *
 * Comprehensive form builder for creating HTML forms with CSRF protection and validation.
 */
class FormBuilder
{
    protected string $action;
    protected string $method;
    protected array $fields = [];
    protected array $attributes = [];
    protected array $errors = [];
    protected ?string $csrfToken = null;
    protected bool $addCsrf = true;

    public function __construct(string $action = '', string $method = 'POST', array $attributes = [])
    {
        $this->action = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
        $this->method = strtoupper($method);
        $this->attributes = $attributes;
    }

    /**
     * Add a text input field.
     */
    public function text(string $name, string $label = '', array $attributes = []): self
    {
        return $this->addField($name, 'text', $label, $attributes);
    }

    /**
     * Add an email input field.
     */
    public function email(string $name, string $label = '', array $attributes = []): self
    {
        return $this->addField($name, 'email', $label, $attributes);
    }

    /**
     * Add a password input field.
     */
    public function password(string $name, string $label = '', array $attributes = []): self
    {
        return $this->addField($name, 'password', $label, $attributes);
    }

    /**
     * Add a textarea field.
     */
    public function textarea(string $name, string $label = '', array $attributes = []): self
    {
        return $this->addField($name, 'textarea', $label, $attributes);
    }

    /**
     * Add a select dropdown.
     */
    public function select(string $name, string $label = '', array $options = [], array $attributes = []): self
    {
        $attributes['options'] = $options;
        return $this->addField($name, 'select', $label, $attributes);
    }

    /**
     * Add a checkbox.
     */
    public function checkbox(string $name, string $label = '', array $attributes = []): self
    {
        return $this->addField($name, 'checkbox', $label, $attributes);
    }

    /**
     * Add a radio button.
     */
    public function radio(string $name, string $value, string $label = '', array $attributes = []): self
    {
        $attributes['value'] = $value;
        return $this->addField($name, 'radio', $label, $attributes);
    }

    /**
     * Add a hidden field.
     */
    public function hidden(string $name, string $value): self
    {
        return $this->addField($name, 'hidden', '', ['value' => $value]);
    }

    /**
     * Add a submit button.
     */
    public function submit(string $value = 'Submit', array $attributes = []): self
    {
        $attributes['value'] = $value;
        return $this->addField('submit', 'submit', '', $attributes);
    }

    /**
     * Add a generic form field.
     */
    public function addField(string $name, string $type = 'text', string $label = '', array $attributes = []): self
    {
        $this->fields[] = [
            'name' => $name,
            'type' => $type,
            'label' => $label,
            'attributes' => $attributes
        ];
        return $this;
    }

    /**
     * Set form-level attributes.
     */
    public function setAttribute(string $key, string $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Set validation errors.
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Set CSRF token.
     */
    public function setCsrfToken(string $token): self
    {
        $this->csrfToken = $token;
        return $this;
    }

    /**
     * Disable CSRF token.
     */
    public function withoutCsrf(): self
    {
        $this->addCsrf = false;
        return $this;
    }

    /**
     * Render the form HTML.
     */
    public function render(): string
    {
        $attrString = $this->renderAttributes($this->attributes);
        $html = "<form action=\"{$this->action}\" method=\"{$this->method}\" {$attrString}>";

        // Add CSRF token if enabled
        if ($this->addCsrf && $this->csrfToken) {
            $html .= "<input type=\"hidden\" name=\"_csrf\" value=\"{$this->csrfToken}\">";
        }

        foreach ($this->fields as $field) {
            $html .= $this->renderField($field);
        }

        $html .= '</form>';
        return $html;
    }

    /**
     * Render a single field.
     */
    protected function renderField(array $field): string
    {
        $html = '';
        $name = $field['name'];
        $type = $field['type'];
        $label = $field['label'];
        $attributes = $field['attributes'];

        // Add wrapper div
        $html .= "<div class=\"form-group\">";

        // Add label
        if (!empty($label) && $type !== 'hidden') {
            $html .= "<label for=\"{$name}\">{$label}</label>";
        }

        // Render field based on type
        switch ($type) {
            case 'textarea':
                $html .= $this->renderTextarea($name, $attributes);
                break;
            case 'select':
                $html .= $this->renderSelect($name, $attributes);
                break;
            case 'checkbox':
            case 'radio':
                $html .= $this->renderCheckable($name, $type, $attributes);
                break;
            case 'submit':
                $html .= $this->renderSubmit($attributes);
                break;
            default:
                $html .= $this->renderInput($name, $type, $attributes);
        }

        // Add error message if exists
        if (isset($this->errors[$name])) {
            $error = htmlspecialchars($this->errors[$name], ENT_QUOTES, 'UTF-8');
            $html .= "<span class=\"error-message\">{$error}</span>";
        }

        $html .= "</div>";
        return $html;
    }

    /**
     * Render input field.
     */
    protected function renderInput(string $name, string $type, array $attributes): string
    {
        $attrString = $this->renderAttributes($attributes);
        return "<input type=\"{$type}\" name=\"{$name}\" id=\"{$name}\" {$attrString}>";
    }

    /**
     * Render textarea field.
     */
    protected function renderTextarea(string $name, array $attributes): string
    {
        $value = $attributes['value'] ?? '';
        unset($attributes['value']);
        $attrString = $this->renderAttributes($attributes);
        return "<textarea name=\"{$name}\" id=\"{$name}\" {$attrString}>{$value}</textarea>";
    }

    /**
     * Render select field.
     */
    protected function renderSelect(string $name, array $attributes): string
    {
        $options = $attributes['options'] ?? [];
        $selected = $attributes['selected'] ?? '';
        unset($attributes['options'], $attributes['selected']);
        
        $attrString = $this->renderAttributes($attributes);
        $html = "<select name=\"{$name}\" id=\"{$name}\" {$attrString}>";
        
        foreach ($options as $value => $label) {
            $isSelected = ($value == $selected) ? ' selected' : '';
            $html .= "<option value=\"{$value}\"{$isSelected}>{$label}</option>";
        }
        
        $html .= "</select>";
        return $html;
    }

    /**
     * Render checkbox or radio field.
     */
    protected function renderCheckable(string $name, string $type, array $attributes): string
    {
        $value = $attributes['value'] ?? '1';
        $checked = !empty($attributes['checked']) ? ' checked' : '';
        unset($attributes['value'], $attributes['checked']);
        
        $attrString = $this->renderAttributes($attributes);
        return "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\" {$attrString}{$checked}>";
    }

    /**
     * Render submit button.
     */
    protected function renderSubmit(array $attributes): string
    {
        $value = $attributes['value'] ?? 'Submit';
        unset($attributes['value']);
        $attrString = $this->renderAttributes($attributes);
        return "<button type=\"submit\" {$attrString}>{$value}</button>";
    }

    /**
     * Helper to render attributes.
     */
    protected function renderAttributes(array $attributes): string
    {
        $parts = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                }
            } else {
                $escapedKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                $escapedValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                $parts[] = "{$escapedKey}=\"{$escapedValue}\"";
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Get form HTML as string.
     */
    public function __toString(): string
    {
        return $this->render();
    }
}

