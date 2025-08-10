<?php
/**
 * Class NotificationQueue
 *
 * A simple in-memory queue for managing and processing notifications.
 */
class NotificationQueue
{
    protected array $queue = [];

    /**
     * Add a notification to the queue.
     */
    public function enqueue(string $recipient, string $message, array $options = []): void
    {
        $this->queue[] = [
            'recipient' => $recipient,
            'message'   => $message,
            'options'   => $options,
            'timestamp' => time(),
        ];
    }

    /**
     * Retrieve and remove the next notification from the queue.
     */
    public function dequeue(): ?array
    {
        return array_shift($this->queue);
    }

    /**
     * Peek at the next notification without removing it.
     */
    public function peek(): ?array
    {
        return $this->queue[0] ?? null;
    }

    /**
     * Check if the queue is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->queue);
    }

    /**
     * Get the total number of notifications in the queue.
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * Process all notifications with a given handler.
     */
    public function process(callable $handler): void
    {
        while (!$this->isEmpty()) {
            $notification = $this->dequeue();
            $handler($notification);
        }
    }
}
