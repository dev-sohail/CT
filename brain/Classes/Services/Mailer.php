<?php

declare(strict_types=1);

/**
 * Class Mailer
 *
 * Comprehensive mailer class supporting plain text, HTML emails, attachments, and templates.
 */
class Mailer
{
    protected string $from;
    protected string $fromName = '';
    protected array $headers = [];
    protected array $attachments = [];
    protected array $config = [];

    public function __construct(string $from, string $fromName = '', array $config = [])
    {
        $this->from = $from;
        $this->fromName = $fromName;
        $this->config = $config;
        $this->initializeHeaders();
    }

    /**
     * Initialize default headers.
     */
    protected function initializeHeaders(): void
    {
        $from = $this->fromName 
            ? "{$this->fromName} <{$this->from}>"
            : $this->from;

        $this->headers = [
            'From' => $from,
            'Reply-To' => $this->config['reply_to'] ?? $this->from,
            'X-Mailer' => 'CyberTirah Framework Mailer',
            'MIME-Version' => '1.0'
        ];
    }

    /**
     * Set a custom header.
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Add an attachment.
     */
    public function addAttachment(string $filePath, string $fileName = ''): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Attachment file not found: {$filePath}");
        }

        $this->attachments[] = [
            'path' => $filePath,
            'name' => $fileName ?: basename($filePath)
        ];
    }

    /**
     * Clear all attachments.
     */
    public function clearAttachments(): void
    {
        $this->attachments = [];
    }

    /**
     * Send a plain text email.
     */
    public function sendText(string $to, string $subject, string $message): bool
    {
        return $this->send($to, $subject, $message, 'text/plain');
    }

    /**
     * Send an HTML email.
     */
    public function sendHtml(string $to, string $subject, string $html): bool
    {
        return $this->send($to, $subject, $html, 'text/html');
    }

    /**
     * Send email with template.
     */
    public function sendTemplate(string $to, string $subject, string $templatePath, array $data = []): bool
    {
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Email template not found: {$templatePath}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $templatePath;
        $html = ob_get_clean();

        return $this->sendHtml($to, $subject, $html);
    }

    /**
     * Send email (core method).
     */
    protected function send(string $to, string $subject, string $body, string $contentType): bool
    {
        $boundary = md5(uniqid(time()));

        if (!empty($this->attachments)) {
            return $this->sendWithAttachments($to, $subject, $body, $contentType, $boundary);
        }

        return $this->sendSimple($to, $subject, $body, $contentType);
    }

    /**
     * Send simple email without attachments.
     */
    protected function sendSimple(string $to, string $subject, string $body, string $contentType): bool
    {
        $headers = $this->headers;
        $headers['Content-Type'] = "{$contentType}; charset=UTF-8";

        $headerString = $this->buildHeaderString($headers);

        return mail($to, $subject, $body, $headerString);
    }

    /**
     * Send email with attachments.
     */
    protected function sendWithAttachments(string $to, string $subject, string $body, string $contentType, string $boundary): bool
    {
        $headers = $this->headers;
        $headers['Content-Type'] = "multipart/mixed; boundary=\"{$boundary}\"";

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body . "\r\n\r\n";

        foreach ($this->attachments as $attachment) {
            $content = file_get_contents($attachment['path']);
            $encoded = chunk_split(base64_encode($content));
            $mimeType = mime_content_type($attachment['path']) ?: 'application/octet-stream';

            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: {$mimeType}; name=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
            $message .= $encoded . "\r\n";
        }

        $message .= "--{$boundary}--";

        $headerString = $this->buildHeaderString($headers);

        return mail($to, $subject, $message, $headerString);
    }

    /**
     * Build header string from array.
     */
    protected function buildHeaderString(array $headers): string
    {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }
        return implode("\r\n", $headerLines);
    }

    /**
     * Send to multiple recipients.
     */
    public function sendBulk(array $recipients, string $subject, string $body, bool $isHtml = false): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $success = $isHtml 
                ? $this->sendHtml($recipient, $subject, $body)
                : $this->sendText($recipient, $subject, $body);

            $results[$recipient] = $success;
        }

        return $results;
    }

    /**
     * Validate email address.
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Set reply-to address.
     */
    public function setReplyTo(string $email, string $name = ''): void
    {
        $replyTo = $name ? "{$name} <{$email}>" : $email;
        $this->setHeader('Reply-To', $replyTo);
    }

    /**
     * Set CC recipients.
     */
    public function setCc(string|array $emails): void
    {
        $ccList = is_array($emails) ? implode(', ', $emails) : $emails;
        $this->setHeader('Cc', $ccList);
    }

    /**
     * Set BCC recipients.
     */
    public function setBcc(string|array $emails): void
    {
        $bccList = is_array($emails) ? implode(', ', $emails) : $emails;
        $this->setHeader('Bcc', $bccList);
    }

    /**
     * Set email priority.
     */
    public function setPriority(int $priority = 3): void
    {
        $priority = max(1, min(5, $priority));
        $this->setHeader('X-Priority', (string)$priority);
    }
}

