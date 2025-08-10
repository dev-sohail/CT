<?php

class Template
{
    protected string $viewPath;
    protected string $extension = '.ct.php';

    protected ?string $role = null;
    protected ?string $module = null;

    protected array $data = [];
    protected ?string $layout = null;

    public function __construct(?string $role = null, ?string $module = null)
    {
        $this->role = $role ?? defined('DEFAULT_ROLE') ? DEFAULT_ROLE : 'App';
        $this->module = $module ?? defined('DEFAULT_MODULE') ? DEFAULT_MODULE : 'Main';
        if (defined('DIR_MODULES')) {
            $this->viewPath = rtrim(DIR_MODULES, '/') . "/{$this->role}/{$this->module}/Views/";
        } else {
            $this->viewPath = __DIR__ . '/../../../Views/';
        }
    }

    /**
     * Assign variables to view
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Assign multiple variables at once
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Define layout file (optional)
     */
    public function setLayout(string $layoutFile): void
    {
        $this->layout = $layoutFile;
    }

    /**
     * Render a view file
     */
    public function render(string $viewFile, array $extraData = []): void
    {
        $viewFullPath = $this->getViewFilePath($viewFile);
        $data = array_merge($this->data, $extraData);

        if (!file_exists($viewFullPath)) {
            throw new Exception("View [$viewFullPath] not found.");
        }

        // Extract variables for view
        extract($data);

        // Capture view content
        ob_start();
        include $viewFullPath;
        $content = ob_get_clean();

        if ($this->layout) {
            $layoutFullPath = $this->getViewFilePath($this->layout);
            if (!file_exists($layoutFullPath)) {
                throw new Exception("Layout [$layoutFullPath] not found.");
            }

            // Content will be available inside layout
            extract(['content' => $content] + $data);
            include $layoutFullPath;
        } else {
            echo $content;
        }
    }

    /**
     * Resolve full path to view
     */
    protected function getViewFilePath(string $file): string
    {
        $base = rtrim($this->viewPath, '/');
        $candidates = [
            $base . '/' . rtrim($file, '.ct.php') . '.ct.php',
            $base . '/' . rtrim($file, '.php') . '.php',
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }
        return $candidates[0];
    }
}
