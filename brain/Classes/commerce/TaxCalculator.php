<?php
/**
 * Class TaxCalculator
 *
 * Handles tax rate management and calculation for amounts.
 */
class TaxCalculator
{
    protected array $taxRates = [];

    /**
     * Set a tax rate for a region or category.
     */
    public function setRate(string $key, float $rate): void
    {
        $this->taxRates[$key] = $rate;
    }

    /**
     * Remove a tax rate.
     */
    public function removeRate(string $key): void
    {
        unset($this->taxRates[$key]);
    }

    /**
     * Get a tax rate.
     */
    public function getRate(string $key): ?float
    {
        return $this->taxRates[$key] ?? null;
    }

    /**
     * Calculate tax for an amount based on a key.
     */
    public function calculate(string $key, float $amount): float
    {
        if (!isset($this->taxRates[$key])) {
            return 0.0;
        }

        return $amount * ($this->taxRates[$key] / 100);
    }

    /**
     * Calculate total with tax.
     */
    public function calculateTotal(string $key, float $amount): float
    {
        return $amount + $this->calculate($key, $amount);
    }

    /**
     * List all tax rates.
     */
    public function listRates(): array
    {
        return $this->taxRates;
    }
}
