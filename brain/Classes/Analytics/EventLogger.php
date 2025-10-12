<?php

declare(strict_types=1);

/**
 * Event Logger
 * 
 * Handles logging of application events
 */
class EventLogger
{
    private string $logFile;
    private bool $enabled;

    public function __construct(string $logFile = 'events.log', bool $enabled = true)
    {
        $this->logFile = $logFile;
        $this->enabled = $enabled;
    }

    /**
     * Log an event
     */
    public function log(string $event, array $data = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'data' => $data
        ];

        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Enable/disable logging
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}