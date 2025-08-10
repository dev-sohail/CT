<?php
/**
 * Class InvoiceGenerator
 *
 * Generates invoices with items, taxes, discounts, and totals.
 */
class InvoiceGenerator
{
    protected array $items = [];
    protected float $taxRate = 0.0;
    protected float $discount = 0.0; // as a percentage
    protected string $currency = 'USD';

    /**
     * Add an item to the invoice.
     */
    public function addItem(string $description, int $quantity, float $unitPrice): void
    {
        $this->items[] = [
            'description' => $description,
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'total'       => $quantity * $unitPrice,
        ];
    }

    /**
     * Set tax rate as percentage.
     */
    public function setTaxRate(float $rate): void
    {
        $this->taxRate = $rate;
    }

    /**
     * Set discount as percentage.
     */
    public function setDiscount(float $discount): void
    {
        $this->discount = $discount;
    }

    /**
     * Set currency.
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = strtoupper($currency);
    }

    /**
     * Calculate subtotal before tax and discount.
     */
    public function getSubtotal(): float
    {
        return array_sum(array_column($this->items, 'total'));
    }

    /**
     * Calculate discount amount.
     */
    public function getDiscountAmount(): float
    {
        return $this->getSubtotal() * ($this->discount / 100);
    }

    /**
     * Calculate tax amount after discount.
     */
    public function getTaxAmount(): float
    {
        $taxableAmount = $this->getSubtotal() - $this->getDiscountAmount();
        return $taxableAmount * ($this->taxRate / 100);
    }

    /**
     * Calculate total amount payable.
     */
    public function getTotal(): float
    {
        return ($this->getSubtotal() - $this->getDiscountAmount()) + $this->getTaxAmount();
    }

    /**
     * Generate invoice as an array.
     */
    public function generate(): array
    {
        return [
            'currency'       => $this->currency,
            'items'          => $this->items,
            'subtotal'       => $this->getSubtotal(),
            'discount_rate'  => $this->discount,
            'discount_amount'=> $this->getDiscountAmount(),
            'tax_rate'       => $this->taxRate,
            'tax_amount'     => $this->getTaxAmount(),
            'total'          => $this->getTotal(),
            'date'           => date('Y-m-d H:i:s'),
        ];
    }
}
