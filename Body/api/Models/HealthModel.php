<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Health Check Model
 */
class HealthModel extends Model
{
    /**
     * Perform health check
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'filesystem' => $this->checkFilesystem(),
            'memory' => $this->checkMemory()
        ];
        
        $allHealthy = !in_array(false, array_values($checks), true);
        
        return [
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'checks' => $checks,
            'version' => '2.0.0',
            'uptime' => $this->getUptime()
        ];
    }
    
    /**
     * Check database connectivity
     */
    private function checkDatabase(): bool
    {
        try {
            $this->db->query("SELECT 1");
            return true;
        } catch (\Exception $e) {
            error_log("Health check - Database error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check filesystem write access
     */
    private function checkFilesystem(): bool
    {
        $testFile = ROOT . '/storage/temp/health_check.txt';
        try {
            file_put_contents($testFile, 'test');
            $result = file_exists($testFile);
            @unlink($testFile);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check memory usage
     */
    private function checkMemory(): bool
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsed = memory_get_usage(true);
        
        // Convert limit to bytes
        $limit = $this->convertToBytes($memoryLimit);
        
        // Check if we're using less than 80% of available memory
        return ($memoryUsed / $limit) < 0.8;
    }
    
    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            return gmdate('H:i:s', (int)$uptime);
        }
        return 'N/A';
    }
    
    /**
     * Convert memory notation to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int)$value;
        
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}

