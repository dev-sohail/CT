<?php
namespace Services;

class QueryLogger {
    private static $enabled = false;
    private static $queries = [];
    private static $max = 200;
    private static $slowMs = 100.0;

    public static function enable() { self::$enabled = true; }
    public static function disable() { self::$enabled = false; }
    public static function enabled() { return self::$enabled; }

    public static function setSlowThreshold(float $ms): void {
        self::$slowMs = max(0.0, $ms);
    }

    public static function log(string $sql, array $params = [], float $ms = 0.0, ?\Throwable $error = null): void {
        if (!self::$enabled) return;
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => round($ms, 2),
            'error' => $error ? $error->getMessage() : null,
            'at' => date('c'),
        ];
        if (count(self::$queries) > self::$max) {
            array_shift(self::$queries);
        }
        if ($ms > self::$slowMs) {
            error_log(sprintf(
                'Slow query detected: %.2fms | %s | %s',
                $ms,
                $sql,
                json_encode($params)
            ));
        }
    }

    public static function all(): array { return self::$queries; }
    public static function clear(): void { self::$queries = []; }

    public static function summary(): array {
        $count = count(self::$queries);
        $total = 0.0;
        $errors = 0;
        foreach (self::$queries as $q) {
            $total += (float)($q['time'] ?? 0);
            if (!empty($q['error'])) $errors++;
        }
        return [
            'count' => $count,
            'total_ms' => round($total, 2),
            'errors' => $errors,
            'queries' => array_slice(array_reverse(self::$queries), 0, 100),
        ];
    }
}
