<?php
/**
 * Class SecurityScanner
 *
 * Scans input data, files, and configurations for potential security risks.
 */
class SecurityScanner
{
    /**
     * Scan a string for common XSS patterns.
     */
    public function scanForXSS(string $input): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/on\w+\s*=\s*"[^"]*"/i',
            '/javascript:/i'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true; // Potential XSS found
            }
        }
        return false;
    }

    /**
     * Scan a string for SQL injection patterns.
     */
    public function scanForSQLInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bUPDATE\b)/i',
            '/(--|#|\/\*)/i',
            '/(\bDROP\b|\bALTER\b|\bCREATE\b)/i'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true; // Potential SQL Injection found
            }
        }
        return false;
    }

    /**
     * Scan a file for potentially dangerous extensions.
     */
    public function scanFileExtension(string $filename, array $allowedExtensions = ['jpg','png','gif','pdf']): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions, true);
    }

    /**
     * Scan configuration values for unsafe settings.
     */
    public function scanConfig(array $config): array
    {
        $issues = [];

        if (!empty($config['display_errors'])) {
            $issues[] = 'Display errors is enabled — should be disabled in production.';
        }
        if (empty($config['encryption_key'])) {
            $issues[] = 'Encryption key is missing.';
        }

        return $issues;
    }
}
