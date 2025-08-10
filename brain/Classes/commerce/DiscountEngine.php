<?php
/**
 * Class DiscountEngine
 *
 * Manages discounts and applies them to prices or carts.
 */
class DiscountEngine
{
    protected array $discounts = [];

    /**
     * Add a discount rule.
     * Types: 'fixed', 'percent'.
     */
    public function addDiscount(string $code, string $type, float $value, ?callable $condition = null): void
    {
        $this->discounts[$code] = [
            'type'      => $type,
            'value'     => $value,
            'condition' => $condition,
        ];
    }

    /**
     * Remove a discount rule.
     */
    public function removeDiscount(string $code): void
    {
        unset($this->discounts[$code]);
    }

    /**
     * Apply a discount code to a price.
     */
    public function apply(string $code, float $price, array $context = []): float
    {
        if (!isset($this->discounts[$code])) {
            return $price;
        }

        $discount = $this->discounts[$code];

        // Check condition
        if ($discount['condition'] && !$discount['condition']($context)) {
            return $price;
        }

        if ($discount['type'] === 'fixed') {
            $price -= $discount['value'];
        } elseif ($discount['type'] === 'percent') {
            $price -= ($price * ($discount['value'] / 100));
        }

        return max(0, $price);
    }

    /**
     * List all discount codes.
     */
    public function listDiscounts(): array
    {
        return array_keys($this->discounts);
    }
}
