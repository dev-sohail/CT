<?php
/**
 * Class MultiCurrency
 *
 * Handles currency exchange rates and conversions.
 */
class MultiCurrency
{
    protected array $rates = [];
    protected string $baseCurrency;

    public function __construct(string $baseCurrency = 'USD')
    {
        $this->baseCurrency = strtoupper($baseCurrency);
    }

    /**
     * Set the exchange rate relative to the base currency.
     */
    public function setRate(string $currency, float $rate): void
    {
        $this->rates[strtoupper($currency)] = $rate;
    }

    /**
     * Remove a currency rate.
     */
    public function removeRate(string $currency): void
    {
        unset($this->rates[strtoupper($currency)]);
    }

    /**
     * Get the exchange rate for a currency.
     */
    public function getRate(string $currency): ?float
    {
        return $this->rates[strtoupper($currency)] ?? null;
    }

    /**
     * Convert an amount from base currency to another currency.
     */
    public function convertTo(string $currency, float $amount): ?float
    {
        $currency = strtoupper($currency);
        if (!isset($this->rates[$currency])) {
            return null;
        }

        return $amount * $this->rates[$currency];
    }

    /**
     * Convert an amount from another currency to base currency.
     */
    public function convertFrom(string $currency, float $amount): ?float
    {
        $currency = strtoupper($currency);
        if (!isset($this->rates[$currency]) || $this->rates[$currency] == 0) {
            return null;
        }

        return $amount / $this->rates[$currency];
    }

    /**
     * List all available currencies and rates.
     */
    public function listRates(): array
    {
        return $this->rates;
    }
}
