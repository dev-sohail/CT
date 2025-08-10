<?php
/**
 * Class Cart
 *
 * A simple shopping cart manager with item management and total calculation.
 */
class Cart
{
    protected array $items = [];

    /**
     * Add an item to the cart.
     */
    public function addItem(string $id, string $name, float $price, int $quantity = 1): void
    {
        if (isset($this->items[$id])) {
            $this->items[$id]['quantity'] += $quantity;
        } else {
            $this->items[$id] = [
                'name'     => $name,
                'price'    => $price,
                'quantity' => $quantity,
            ];
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(string $id): void
    {
        unset($this->items[$id]);
    }

    /**
     * Update the quantity of an item.
     */
    public function updateQuantity(string $id, int $quantity): void
    {
        if (isset($this->items[$id])) {
            if ($quantity <= 0) {
                $this->removeItem($id);
            } else {
                $this->items[$id]['quantity'] = $quantity;
            }
        }
    }

    /**
     * Get all items in the cart.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Calculate the total cost of the cart.
     */
    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * Empty the cart.
     */
    public function clear(): void
    {
        $this->items = [];
    }
}
