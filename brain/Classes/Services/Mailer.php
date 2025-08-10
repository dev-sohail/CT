<?php
/**
 * Class Mailer
 *
 * A simple mailer class to send plain text and HTML emails.
 */
class Mailer
{
    protected string $from;
    protected array $headers = [];

    public function __construct(string $from)
    {
        $this->from = $from;
        $this->headers[] = "From: {$from}";
        $this->headers[] = 'MIME-Version: 1.0';
    }

    /**
     * Set an additional header.
     */
    public function setHeader(string $header): void
    {
        $this->headers[] = $header;
    }

    /**
     * Send a plain text email.
     */
    public function sendText(string $to, string $subject, string $message): bool
    {
        $headers = $this->headers;
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Send an HTML email.
     */
    public function sendHtml(string $to, string $subject, string $html): bool
    {
        $headers = $this->headers;
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return mail($to, $subject, $html, implode("\r\n", $headers));
    }
}
