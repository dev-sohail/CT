<?php
/**
 * Class FormBuilder
 *
 * A simple form builder for creating HTML forms dynamically.
 */
class FormBuilder
{
    protected string $action;
    protected string $method;
    protected array $fields = [];
    protected array $attributes = [];

    public function __construct(string $action = '', string $method = 'POST', array $attributes = [])
    {
        $this->action = $action;
        $this->method = strtoupper($method);
        $this->attributes = $attributes;
    }

    /**
     * Add a form field.
     */
    public function addField(string $name, string $type = 'text', array $attributes = []): self
    {
        $this->fields[] = compact('name', 'type', 'attributes');
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
     * Render the form HTML.
     */
    public function render(): string
    {
        $attrString = $this->renderAttributes($this->attributes);
        $html = "<form action=\"{$this->action}\" method=\"{$this->method}\" {$attrString}>";

        foreach ($this->fields as $field) {
            $fieldAttr = $this->renderAttributes($field['attributes']);
            $html .= "<input type=\"{$field['type']}\" name=\"{$field['name']}\" {$fieldAttr}>";
        }

        $html .= '</form>';
        return $html;
    }

    /**
     * Helper to render attributes.
     */
    protected function renderAttributes(array $attributes): string
    {
        $parts = [];
        foreach ($attributes as $key => $value) {
            $parts[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        return implode(' ', $parts);
    }
}
