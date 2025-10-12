<?php

declare(strict_types=1);

/**
 * AlertManager Class
 * 
 * Manages alerts and notifications for system monitoring
 */
class AlertManager
{
    private array $alerts = [];
    private array $rules = [];
    private array $channels = [];
    private int $maxAlerts = 1000;
    private bool $enabled = true;

    /**
     * Constructor
     * 
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
        $this->initializeDefaultChannels();
    }

    /**
     * Configure alert manager
     * 
     * @param array $config Configuration options
     */
    private function configure(array $config): void
    {
        if (isset($config['max_alerts'])) {
            $this->maxAlerts = (int) $config['max_alerts'];
        }
        
        if (isset($config['enabled'])) {
            $this->enabled = (bool) $config['enabled'];
        }
    }

    /**
     * Initialize default notification channels
     */
    private function initializeDefaultChannels(): void
    {
        $this->channels['log'] = function($alert) {
            error_log("ALERT: {$alert['title']} - {$alert['message']}");
        };

        $this->channels['email'] = function($alert) {
            // Email implementation would go here
            error_log("EMAIL ALERT: {$alert['title']}");
        };
    }

    /**
     * Add an alert
     * 
     * @param string $title Alert title
     * @param string $message Alert message
     * @param string $level Alert level (info, warning, error, critical)
     * @param array $metadata Additional metadata
     */
    public function addAlert(string $title, string $message, string $level = 'info', array $metadata = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $alert = [
            'id' => $this->generateAlertId(),
            'title' => $title,
            'message' => $message,
            'level' => $level,
            'timestamp' => time(),
            'metadata' => $metadata,
            'acknowledged' => false,
            'resolved' => false
        ];

        $this->alerts[] = $alert;

        // Limit number of alerts
        if (count($this->alerts) > $this->maxAlerts) {
            array_shift($this->alerts);
        }

        // Send notifications
        $this->sendNotifications($alert);

        // Check rules
        $this->checkRules($alert);
    }

    /**
     * Generate unique alert ID
     * 
     * @return string Alert ID
     */
    private function generateAlertId(): string
    {
        return 'alert_' . uniqid() . '_' . time();
    }

    /**
     * Send notifications for an alert
     * 
     * @param array $alert Alert data
     */
    private function sendNotifications(array $alert): void
    {
        foreach ($this->channels as $channelName => $channel) {
            try {
                if (is_callable($channel)) {
                    $channel($alert);
                }
            } catch (Throwable $e) {
                error_log("Failed to send alert via {$channelName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Check alert rules
     * 
     * @param array $alert Alert data
     */
    private function checkRules(array $alert): void
    {
        foreach ($this->rules as $rule) {
            if ($this->matchesRule($alert, $rule)) {
                $this->executeRuleAction($alert, $rule);
            }
        }
    }

    /**
     * Check if alert matches a rule
     * 
     * @param array $alert Alert data
     * @param array $rule Rule data
     * @return bool True if matches
     */
    private function matchesRule(array $alert, array $rule): bool
    {
        // Check level
        if (isset($rule['level']) && $alert['level'] !== $rule['level']) {
            return false;
        }

        // Check title pattern
        if (isset($rule['title_pattern']) && !preg_match($rule['title_pattern'], $alert['title'])) {
            return false;
        }

        // Check message pattern
        if (isset($rule['message_pattern']) && !preg_match($rule['message_pattern'], $alert['message'])) {
            return false;
        }

        // Check metadata
        if (isset($rule['metadata'])) {
            foreach ($rule['metadata'] as $key => $value) {
                if (!isset($alert['metadata'][$key]) || $alert['metadata'][$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Execute rule action
     * 
     * @param array $alert Alert data
     * @param array $rule Rule data
     */
    private function executeRuleAction(array $alert, array $rule): void
    {
        if (isset($rule['action']) && is_callable($rule['action'])) {
            try {
                $rule['action']($alert);
            } catch (Throwable $e) {
                error_log("Failed to execute rule action: " . $e->getMessage());
            }
        }
    }

    /**
     * Add a rule
     * 
     * @param string $name Rule name
     * @param array $conditions Rule conditions
     * @param callable $action Rule action
     */
    public function addRule(string $name, array $conditions, callable $action): void
    {
        $this->rules[$name] = [
            'name' => $name,
            'conditions' => $conditions,
            'action' => $action,
            'enabled' => true
        ];
    }

    /**
     * Remove a rule
     * 
     * @param string $name Rule name
     */
    public function removeRule(string $name): void
    {
        unset($this->rules[$name]);
    }

    /**
     * Add a notification channel
     * 
     * @param string $name Channel name
     * @param callable $callback Channel callback
     */
    public function addChannel(string $name, callable $callback): void
    {
        $this->channels[$name] = $callback;
    }

    /**
     * Remove a notification channel
     * 
     * @param string $name Channel name
     */
    public function removeChannel(string $name): void
    {
        unset($this->channels[$name]);
    }

    /**
     * Get all alerts
     * 
     * @param array $filters Optional filters
     * @return array Array of alerts
     */
    public function getAlerts(array $filters = []): array
    {
        $alerts = $this->alerts;

        if (!empty($filters)) {
            $alerts = array_filter($alerts, function($alert) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (isset($alert[$key]) && $alert[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            });
        }

        return array_values($alerts);
    }

    /**
     * Get alerts by level
     * 
     * @param string $level Alert level
     * @return array Array of alerts
     */
    public function getAlertsByLevel(string $level): array
    {
        return $this->getAlerts(['level' => $level]);
    }

    /**
     * Get unacknowledged alerts
     * 
     * @return array Array of unacknowledged alerts
     */
    public function getUnacknowledgedAlerts(): array
    {
        return $this->getAlerts(['acknowledged' => false]);
    }

    /**
     * Get unresolved alerts
     * 
     * @return array Array of unresolved alerts
     */
    public function getUnresolvedAlerts(): array
    {
        return $this->getAlerts(['resolved' => false]);
    }

    /**
     * Acknowledge an alert
     * 
     * @param string $alertId Alert ID
     * @return bool Success status
     */
    public function acknowledgeAlert(string $alertId): bool
    {
        foreach ($this->alerts as &$alert) {
            if ($alert['id'] === $alertId) {
                $alert['acknowledged'] = true;
                $alert['acknowledged_at'] = time();
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve an alert
     * 
     * @param string $alertId Alert ID
     * @return bool Success status
     */
    public function resolveAlert(string $alertId): bool
    {
        foreach ($this->alerts as &$alert) {
            if ($alert['id'] === $alertId) {
                $alert['resolved'] = true;
                $alert['resolved_at'] = time();
                return true;
            }
        }
        return false;
    }

    /**
     * Clear old alerts
     * 
     * @param int $maxAge Maximum age in seconds
     */
    public function clearOldAlerts(int $maxAge = 86400): void
    {
        $cutoff = time() - $maxAge;
        
        $this->alerts = array_filter($this->alerts, function($alert) use ($cutoff) {
            return $alert['timestamp'] > $cutoff;
        });
    }

    /**
     * Get alert statistics
     * 
     * @return array Alert statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_alerts' => count($this->alerts),
            'by_level' => [],
            'acknowledged' => 0,
            'unacknowledged' => 0,
            'resolved' => 0,
            'unresolved' => 0
        ];

        foreach ($this->alerts as $alert) {
            // Count by level
            if (!isset($stats['by_level'][$alert['level']])) {
                $stats['by_level'][$alert['level']] = 0;
            }
            $stats['by_level'][$alert['level']]++;

            // Count acknowledged/unacknowledged
            if ($alert['acknowledged']) {
                $stats['acknowledged']++;
            } else {
                $stats['unacknowledged']++;
            }

            // Count resolved/unresolved
            if ($alert['resolved']) {
                $stats['resolved']++;
            } else {
                $stats['unresolved']++;
            }
        }

        return $stats;
    }

    /**
     * Enable alert manager
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable alert manager
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if alert manager is enabled
     * 
     * @return bool True if enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Clear all alerts
     */
    public function clearAllAlerts(): void
    {
        $this->alerts = [];
    }

    /**
     * Get rules
     * 
     * @return array Array of rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get channels
     * 
     * @return array Array of channels
     */
    public function getChannels(): array
    {
        return array_keys($this->channels);
    }
}