<?php

declare(strict_types=1);

/**
 * AnomalyDetector Class
 * 
 * Detects anomalies in data streams using statistical methods
 */
class AnomalyDetector
{
    private array $dataHistory = [];
    private float $threshold = 2.0;
    private int $windowSize = 100;

    /**
     * Constructor
     * 
     * @param float $threshold Anomaly detection threshold
     * @param int $windowSize Size of the sliding window
     */
    public function __construct(float $threshold = 2.0, int $windowSize = 100)
    {
        $this->threshold = $threshold;
        $this->windowSize = $windowSize;
    }

    /**
     * Add data point to history
     * 
     * @param float $value Data point value
     */
    public function addDataPoint(float $value): void
    {
        $this->dataHistory[] = $value;
        
        // Keep only the last windowSize points
        if (count($this->dataHistory) > $this->windowSize) {
            array_shift($this->dataHistory);
        }
    }

    /**
     * Detect if a value is anomalous
     * 
     * @param float $value Value to check
     * @return bool True if anomalous
     */
    public function isAnomalous(float $value): bool
    {
        if (count($this->dataHistory) < 10) {
            return false; // Not enough data
        }

        $mean = array_sum($this->dataHistory) / count($this->dataHistory);
        $variance = $this->calculateVariance($this->dataHistory, $mean);
        $stdDev = sqrt($variance);

        if ($stdDev == 0) {
            return false; // No variation in data
        }

        $zScore = abs($value - $mean) / $stdDev;
        return $zScore > $this->threshold;
    }

    /**
     * Calculate variance
     * 
     * @param array $data Data array
     * @param float $mean Mean value
     * @return float Variance
     */
    private function calculateVariance(array $data, float $mean): float
    {
        $sumSquaredDiffs = 0;
        foreach ($data as $value) {
            $sumSquaredDiffs += pow($value - $mean, 2);
        }
        return $sumSquaredDiffs / count($data);
    }

    /**
     * Get anomaly score for a value
     * 
     * @param float $value Value to score
     * @return float Anomaly score (0-1)
     */
    public function getAnomalyScore(float $value): float
    {
        if (count($this->dataHistory) < 10) {
            return 0.0;
        }

        $mean = array_sum($this->dataHistory) / count($this->dataHistory);
        $variance = $this->calculateVariance($this->dataHistory, $mean);
        $stdDev = sqrt($variance);

        if ($stdDev == 0) {
            return 0.0;
        }

        $zScore = abs($value - $mean) / $stdDev;
        return min(1.0, $zScore / $this->threshold);
    }

    /**
     * Set threshold
     * 
     * @param float $threshold New threshold
     */
    public function setThreshold(float $threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * Get current threshold
     * 
     * @return float Current threshold
     */
    public function getThreshold(): float
    {
        return $this->threshold;
    }

    /**
     * Clear data history
     */
    public function clearHistory(): void
    {
        $this->dataHistory = [];
    }

    /**
     * Get data history size
     * 
     * @return int Number of data points
     */
    public function getHistorySize(): int
    {
        return count($this->dataHistory);
    }
}