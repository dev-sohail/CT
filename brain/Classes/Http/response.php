<?php

class Response
{
    private static array $headers = [];
    private $level;
    private $output;

    /**
     * Send a 404 Not Found response and exit.
     */
    public static function send404(string $message = '404 Not Found'): void
    {
        http_response_code(404);
        self::sendContent($message);
    }

    /**
     * Send a 500 Internal Server Error response and exit.
     */
    public static function send500(string $message = '500 Internal Server Error'): void
    {
        http_response_code(500);
        self::sendContent($message);
    }

    /**
     * Send a 403 Forbidden response and exit.
     */
    public static function send403(string $message = '403 Forbidden'): void
    {
        http_response_code(403);
        self::sendContent($message);
    }

    /**
     * Send JSON response with given data.
     *
     * @param mixed $data
     * @param int $statusCode HTTP status code, default 200 OK
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function setCompression(int $level): void
    {
        $this->level = $level;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    // public function view(string $role, string $module, string $template, array $data = []): string
    // {
    //     if (!defined('DS')) {
    //         define('DS', DIRECTORY_SEPARATOR);
    //     }

    //     $file = DIR_MODULES . DS . $role . DS . $module . DS . 'Views' . DS . $template;

    //     if (file_exists($file)) {
    //         extract($data, EXTR_SKIP);
    //         ob_start();
    //         require($file);
    //         return ob_get_clean();
    //     } else {
    //         trigger_error('Error: Could not load template ' . $file . '!', E_USER_ERROR);
    //         exit();
    //     }
    // }


    private function compress(string $data, int $level = 0): string
    {
        $encoding = null;
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                $encoding = 'gzip';
            } elseif (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
                $encoding = 'x-gzip';
            }
        }

        if (!$encoding || !extension_loaded('zlib') || ini_get('zlib.output_compression') || headers_sent() || connection_status()) {
            return $data;
        }

        $this->addHeader('Content-Encoding: ' . $encoding);
        return gzencode($data, $level);
    }

    public function output(): void
    {
        if ($this->output) {
            $output = $this->level ? $this->compress($this->output, $this->level) : $this->output;

            if (!headers_sent()) {
                foreach (self::$headers as $header) {
                    header($header, true);
                }
            }
            echo $output;
        }
    }

    public function jsonResponse(array $data = []): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send plain text or HTML content and exit.
     */
    public static function sendContent(string $content): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Sets the HTTP status code (e.g. 200, 404).
     */
    public static function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    /**
     * Adds a custom HTTP header to the response.
     */
    public static function addHeader(string $header): void
    {
        self::$headers[] = $header;
        header($header);
    }

    /**
     * Redirect to another URL and exit.
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }
}
