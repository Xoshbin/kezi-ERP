<?php

namespace Jmeryar\Inventory\Exceptions\Inventory;

use Brick\Money\Money;
use Exception;
use Jmeryar\Product\Models\Product;

/**
 * Exception thrown when cost data exists but is invalid for inventory operations
 *
 * This exception is used when cost information is found but cannot be used
 * (e.g., negative costs, zero amounts when positive required, currency mismatches)
 */
class InvalidCostDataException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly string $costSource,
        public readonly ?Money $invalidCost = null,
        public readonly string $reason = '',
        ?string $message = null,
    ) {
        $defaultMessage = "Invalid cost data for product '{$product->name}' (ID: {$product->id}) from source '{$costSource}'.";

        if ($this->invalidCost) {
            $defaultMessage .= " Cost value: {$this->invalidCost->getAmount()}.";
        }

        if (! empty($this->reason)) {
            $defaultMessage .= " Reason: {$this->reason}.";
        }

        parent::__construct($message ?? $defaultMessage);
    }

    /**
     * Get the product that caused the exception
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * Get the cost source that provided invalid data
     */
    public function getCostSource(): string
    {
        return $this->costSource;
    }

    /**
     * Get the invalid cost value if available
     */
    public function getInvalidCost(): ?Money
    {
        return $this->invalidCost;
    }

    /**
     * Get the reason why the cost data is invalid
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
