<?php
/**
 * Class Component
 *
 * Represents a reusable UI component with optional rendering logic.
 */
class Component
{
    protected string $name;
    protected array $props = [];
    protected $renderer = null;

    public function __construct(string $name, array $props = [], ?callable $renderer = null)
    {
        $this->name = $name;
        $this->props = $props;
        $this->renderer = $renderer;
    }

    /**
     * Set a property value.
     */
    public function setProp(string $key, $value): void
    {
        $this->props[$key] = $value;
    }

    /**
     * Get a property value.
     */
    public function getProp(string $key)
    {
        return $this->props[$key] ?? null;
    }

    /**
     * Render the component output.
     */
    public function render(): string
    {
        if ($this->renderer) {
            return call_user_func($this->renderer, $this->props);
        }

        // Default rendering as JSON props
        return '<div data-component="' . htmlspecialchars($this->name) . '">' .
               htmlspecialchars(json_encode($this->props)) .
               '</div>';
    }
}
