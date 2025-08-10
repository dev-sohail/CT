<?php
/**
 * Class Timer
 *
 * A simple utility to measure elapsed time for operations.
 */
class Timer
{
    protected float $start;

    /**
     * Start the timer.
     */
    public function start(): void
    {
        $this->start = microtime(true);
    }

    /**
     * Get the elapsed time in seconds since the timer was started.
     */
    public function elapsed(): float
    {
        return microtime(true) - $this->start;
    }

    /**
     * Get the elapsed time formatted with a precision.
     */
    public function elapsedFormatted(int $precision = 4): string
    {
        return number_format($this->elapsed(), $precision) . 's';
    }
}
