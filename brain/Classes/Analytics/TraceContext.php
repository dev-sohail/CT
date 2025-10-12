<?php

declare(strict_types=1);

/**
 * Trace Context
 * 
 * Manages distributed tracing context
 */
class TraceContext
{
    private string $traceId;
    private string $spanId;
    private array $baggage = [];

    public function __construct(string $traceId = null, string $spanId = null)
    {
        $this->traceId = $traceId ?: $this->generateTraceId();
        $this->spanId = $spanId ?: $this->generateSpanId();
    }

    /**
     * Generate trace ID
     */
    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate span ID
     */
    private function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Get trace ID
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * Get span ID
     */
    public function getSpanId(): string
    {
        return $this->spanId;
    }

    /**
     * Set baggage item
     */
    public function setBaggage(string $key, string $value): void
    {
        $this->baggage[$key] = $value;
    }

    /**
     * Get baggage item
     */
    public function getBaggage(string $key): ?string
    {
        return $this->baggage[$key] ?? null;
    }

    /**
     * Get all baggage
     */
    public function getAllBaggage(): array
    {
        return $this->baggage;
    }
}