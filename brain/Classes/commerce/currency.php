<?php
/**
 * Class Currency
 *
 * Handles currency formatting, conversion, and symbol retrieval.
 */
class Currency
{
    protected string $defaultCurrency = 'USD';
    protected array $exchangeRates = [];
    protected array $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'INR' => '₹',
    ];

    public function __construct(string $defaultCurrency = 'USD', array $exchangeRates = [])
    {
        $this->defaultCurrency = $defaultCurrency;
        $this->exchangeRates = $exchangeRates;
    }

    /**
     * Convert an amount from one currency to another.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        if (!isset($this->exchangeRates[$from][$to])) {
            throw new Exception("Exchange rate from $from to $to not available.");
        }

        return $amount * $this->exchangeRates[$from][$to];
    }

    /**
     * Format an amount according to currency.
     */
    public function format(float $amount, string $currency = null): string
    {
        $currency = $currency ?? $this->defaultCurrency;
        $symbol = $this->symbols[$currency] ?? '';
        return $symbol . number_format($amount, 2);
    }

    /**
     * Set an exchange rate between two currencies.
     */
    public function setExchangeRate(string $from, string $to, float $rate): void
    {
        $this->exchangeRates[$from][$to] = $rate;
    }

    /**
     * Get symbol for a currency.
     */
    public function getSymbol(string $currency): string
    {
        return $this->symbols[$currency] ?? '';
    }
}
