<?php

declare(strict_types=1);

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';
    protected static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function json(mixed $data, int $status = 200, int $options = 0): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | $options);
        return $this;
    }

    public function html(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->content = $content;
        return $this;
    }

    public function text(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/plain; charset=utf-8';
        $this->content = $content;
        return $this;
    }

    public function xml(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/xml; charset=utf-8';
        $this->content = $content;
        return $this;
    }

    public function redirect(string $url, int $status = 302): void
    {
        $this->statusCode = $status;
        $this->headers['Location'] = $url;
        $this->send();
        exit;
    }

    public function download(string $filePath, ?string $name = null, array $headers = []): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        $name = $name ?? basename($filePath);
        $this->headers['Content-Type'] = 'application/octet-stream';
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $name . '"';
        $this->headers['Content-Length'] = (string)filesize($filePath);
        
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }

        $this->sendHeaders();
        readfile($filePath);
        exit;
    }

    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
    }

    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown';
        header("HTTP/1.1 {$this->statusCode} {$statusText}");

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }

    public function withCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = true): self
    {
        setcookie($name, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => 'Lax'
        ]);
        return $this;
    }

    public function noCache(): self
    {
        $this->headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        $this->headers['Pragma'] = 'no-cache';
        $this->headers['Expires'] = '0';
        return $this;
    }

    public function cache(int $seconds): self
    {
        $this->headers['Cache-Control'] = "public, max-age=$seconds";
        $this->headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT';
        return $this;
    }

    public static function make(string $content, int $status = 200): self
    {
        return (new self())->setContent($content)->setStatusCode($status);
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
