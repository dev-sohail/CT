<?php
/**
 * Class SupportTicket
 *
 * A simple support ticket management system for creating, updating, and retrieving tickets.
 */
class SupportTicket
{
    protected array $tickets = [];
    protected int $nextId = 1;

    /**
     * Create a new support ticket.
     */
    public function create(string $customer, string $subject, string $message, string $status = 'open'): int
    {
        $id = $this->nextId++;
        $this->tickets[$id] = [
            'id'       => $id,
            'customer' => $customer,
            'subject'  => $subject,
            'message'  => $message,
            'status'   => $status,
            'created'  => time(),
            'updated'  => time(),
        ];
        return $id;
    }

    /**
     * Update an existing ticket's status or message.
     */
    public function update(int $id, array $data): bool
    {
        if (!isset($this->tickets[$id])) {
            return false;
        }
        $this->tickets[$id] = array_merge($this->tickets[$id], $data, ['updated' => time()]);
        return true;
    }

    /**
     * Get a ticket by its ID.
     */
    public function get(int $id): ?array
    {
        return $this->tickets[$id] ?? null;
    }

    /**
     * List all tickets, optionally filtered by status.
     */
    public function list(string $status = null): array
    {
        if ($status === null) {
            return array_values($this->tickets);
        }
        return array_values(array_filter($this->tickets, fn($ticket) => $ticket['status'] === $status));
    }
}
