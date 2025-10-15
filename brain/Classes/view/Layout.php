<?php

declare(strict_types=1);

/**
 * Class Layout
 *
 * Manages layout system with sections and content blocks.
 */
class Layout
{
    protected array $sections = [];
    protected array $stacks = [];
    protected ?string $currentSection = null;
    protected string $layoutPath = '';

    /**
     * Set the layout file path.
     */
    public function setLayout(string $path): void
    {
        $this->layoutPath = $path;
    }

    /**
     * Start a section.
     */
    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End the current section.
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new RuntimeException('No section started');
        }

        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    /**
     * Yield a section's content.
     */
    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Check if a section exists.
     */
    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    /**
     * Push content to a stack.
     */
    public function push(string $name, string $content): void
    {
        if (!isset($this->stacks[$name])) {
            $this->stacks[$name] = [];
        }

        $this->stacks[$name][] = $content;
    }

    /**
     * Start pushing to a stack.
     */
    public function startPush(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End pushing to a stack.
     */
    public function endPush(): void
    {
        if ($this->currentSection === null) {
            throw new RuntimeException('No push started');
        }

        $content = ob_get_clean();
        $this->push($this->currentSection, $content);
        $this->currentSection = null;
    }

    /**
     * Get all content from a stack.
     */
    public function stack(string $name): string
    {
        if (!isset($this->stacks[$name])) {
            return '';
        }

        return implode("\n", $this->stacks[$name]);
    }

    /**
     * Include a partial view.
     */
    public function include(string $path, array $data = []): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Partial view not found: {$path}");
        }

        extract($data, EXTR_SKIP);
        include $path;
    }

    /**
     * Render layout with content.
     */
    public function render(string $content, array $data = []): string
    {
        if (empty($this->layoutPath)) {
            return $content;
        }

        if (!file_exists($this->layoutPath)) {
            throw new RuntimeException("Layout not found: {$this->layoutPath}");
        }

        $this->sections['content'] = $content;
        extract($data, EXTR_SKIP);

        ob_start();
        include $this->layoutPath;
        return ob_get_clean();
    }

    /**
     * Clear all sections and stacks.
     */
    public function clear(): void
    {
        $this->sections = [];
        $this->stacks = [];
        $this->currentSection = null;
    }
}
