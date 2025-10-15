<?php

declare(strict_types=1);

class Template
{
    protected string $viewPath;
    protected array $data = [];
    protected ?string $layout = null;
    protected array $sections = [];
    protected ?string $currentSection = null;

    public function __construct(?string $viewPath = null)
    {
        if ($viewPath) {
            $this->viewPath = rtrim($viewPath, '/');
        } elseif (defined('DIR_BODY')) {
            $this->viewPath = DIR_BODY;
        } else {
            $this->viewPath = defined('ROOT') ? ROOT . '/Body' : __DIR__ . '/../../../Body';
        }
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->resolveViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new RuntimeException("View not found: $view at $viewPath");
        }

        $allData = array_merge($this->data, $data);
        extract($allData, EXTR_SKIP);

        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        if ($this->layout) {
            $layoutPath = $this->resolveViewPath($this->layout);
            
            if (!file_exists($layoutPath)) {
                throw new RuntimeException("Layout not found: {$this->layout} at $layoutPath");
            }

            $this->sections['content'] = $content;
            extract(array_merge($allData, $this->sections), EXTR_SKIP);

            ob_start();
            include $layoutPath;
            return ob_get_clean();
        }

        return $content;
    }

    public function display(string $view, array $data = []): void
    {
        echo $this->render($view, $data);
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    protected function resolveViewPath(string $view): string
    {
        $view = str_replace('.', '/', $view);
        
        $extensions = ['.ct', '.php'];
        
        foreach ($extensions as $ext) {
            $path = $this->viewPath . '/' . $view . $ext;
            if (file_exists($path)) {
                return $path;
            }
        }

        return $this->viewPath . '/' . $view . '.ct';
    }

    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function e(string $value): string
    {
        return $this->escape($value);
    }
}

