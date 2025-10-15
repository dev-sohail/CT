<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class AIModel extends Model
{
    private string $logFile;
    
    public function __construct() {
        parent::__construct();
        $this->logFile = ROOT . '/Storage/logs/ai_requests.json';
    }
    
    public function getStats(): array {
        $logs = $this->loadLogs();
        return [
            'total_requests' => count($logs),
            'today_requests' => $this->countToday($logs),
            'success_rate' => $this->calculateSuccessRate($logs),
            'avg_response_time' => $this->calculateAvgResponseTime($logs)
        ];
    }
    
    public function getRecentLogs(int $limit = 10): array {
        $logs = $this->loadLogs();
        return array_slice(array_reverse($logs), 0, $limit);
    }
    
    public function getConfig(): array {
        return [
            'ai_enabled' => defined('AI_ENABLED') ? AI_ENABLED : false,
            'ai_provider' => defined('AI_PROVIDER') ? AI_PROVIDER : 'Not configured',
            'ai_model' => defined('AI_MODEL') ? AI_MODEL : 'Not configured',
            'max_tokens' => defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 1000
        ];
    }
    
    public function processPrompt(string $prompt): array {
        try {
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'prompt' => $prompt,
                'user' => $_SESSION['username'] ?? 'unknown',
                'response' => 'AI processing simulation: ' . substr($prompt, 0, 50) . '...',
                'success' => true,
                'response_time' => rand(100, 500) . 'ms'
            ];
            
            $this->saveLog($logEntry);
            
            return [
                'success' => true,
                'response' => $logEntry['response']
            ];
        } catch (\Exception $e) {
            error_log("AI process error: " . $e->getMessage());
            return ['success' => false, 'message' => 'AI processing failed'];
        }
    }
    
    public function clearLogs(): array {
        try {
            if (file_exists($this->logFile)) {
                file_put_contents($this->logFile, json_encode([]));
                return ['success' => true, 'message' => 'AI logs cleared successfully'];
            }
            return ['success' => true, 'message' => 'No logs to clear'];
        } catch (\Exception $e) {
            error_log("Clear logs error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to clear logs'];
        }
    }
    
    private function loadLogs(): array {
        if (!file_exists($this->logFile)) {
            return [];
        }
        $content = file_get_contents($this->logFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveLog(array $entry): void {
        $logs = $this->loadLogs();
        $logs[] = $entry;
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        file_put_contents($this->logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
    
    private function countToday(array $logs): int {
        $today = date('Y-m-d');
        return count(array_filter($logs, fn($log) => strpos($log['timestamp'], $today) === 0));
    }
    
    private function calculateSuccessRate(array $logs): string {
        if (empty($logs)) return '0%';
        $successful = count(array_filter($logs, fn($log) => $log['success'] ?? false));
        return round(($successful / count($logs)) * 100, 1) . '%';
    }
    
    private function calculateAvgResponseTime(array $logs): string {
        if (empty($logs)) return '0ms';
        $times = array_map(fn($log) => (int)str_replace('ms', '', $log['response_time'] ?? '0'), $logs);
        return round(array_sum($times) / count($times)) . 'ms';
    }
}

