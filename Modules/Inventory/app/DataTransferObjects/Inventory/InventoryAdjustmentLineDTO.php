<?php

namespace Modules\Inventory\DataTransferObjects\Inventory;

readonly class InventoryAdjustmentLineDTO
{
    public function __construct(
        public int $product_id,
        public int $location_id,
        public float $counted_quantity,
        public float $current_quantity,
        public ?int $lot_id = null,
    ) {
    }

    /**
     * Calculate the adjustment quantity (positive for increase, negative for decrease)
     */
    public function getAdjustmentQuantity(): float
    {
        return $this->counted_quantity - $this->current_quantity;
    }

    /**
     * Check if this line requires an adjustment
     */
    public function requiresAdjustment(): bool
    {
        return abs($this->getAdjustmentQuantity()) > 0.0001; // Account for floating point precision
    }
}
