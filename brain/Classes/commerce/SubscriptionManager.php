<?php
/**
 * Class SubscriptionManager
 *
 * Manages user subscriptions, plans, and renewal dates.
 */
class SubscriptionManager
{
    protected array $subscriptions = [];

    /**
     * Subscribe a user to a plan.
     */
    public function subscribe(int $userId, string $plan, string $startDate, string $endDate): void
    {
        $this->subscriptions[$userId] = [
            'plan'       => $plan,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
    }

    /**
     * Unsubscribe a user.
     */
    public function unsubscribe(int $userId): void
    {
        unset($this->subscriptions[$userId]);
    }

    /**
     * Check if a user has an active subscription.
     */
    public function isActive(int $userId): bool
    {
        if (!isset($this->subscriptions[$userId])) {
            return false;
        }

        $today = date('Y-m-d');
        return $this->subscriptions[$userId]['end_date'] >= $today;
    }

    /**
     * Get subscription details for a user.
     */
    public function getDetails(int $userId): ?array
    {
        return $this->subscriptions[$userId] ?? null;
    }

    /**
     * Renew a subscription.
     */
    public function renew(int $userId, string $newEndDate): void
    {
        if (isset($this->subscriptions[$userId])) {
            $this->subscriptions[$userId]['end_date'] = $newEndDate;
        }
    }

    /**
     * List all active subscriptions.
     */
    public function listActive(): array
    {
        $today = date('Y-m-d');
        return array_filter($this->subscriptions, function ($sub) use ($today) {
            return $sub['end_date'] >= $today;
        });
    }
}
