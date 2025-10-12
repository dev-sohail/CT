<?php

declare(strict_types=1);

/**
 * Heatmap Tracker
 * 
 * Tracks user interactions for heatmap generation
 */
class HeatmapTracker
{
    private array $interactions = [];
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = session_id() ?: uniqid('session_', true);
    }

    /**
     * Track a click event
     */
    public function trackClick(int $x, int $y, string $element = ''): void
    {
        $this->interactions[] = [
            'type' => 'click',
            'x' => $x,
            'y' => $y,
            'element' => $element,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Track a scroll event
     */
    public function trackScroll(int $scrollTop): void
    {
        $this->interactions[] = [
            'type' => 'scroll',
            'scrollTop' => $scrollTop,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get tracked interactions
     */
    public function getInteractions(): array
    {
        return $this->interactions;
    }

    /**
     * Clear interactions
     */
    public function clearInteractions(): void
    {
        $this->interactions = [];
    }
}