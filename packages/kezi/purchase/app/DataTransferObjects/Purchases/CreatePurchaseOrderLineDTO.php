<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

use Brick\Money\Money;
use Carbon\Carbon;

/**
 * Data Transfer Object for creating a Purchase Order Line
 */
readonly class CreatePurchaseOrderLineDTO
{
    public function __construct(
        public int $product_id,
        public string $description,
        public float $quantity,
        public Money $unit_price,
        public ?int $tax_id = null,
        public ?\Kezi\Foundation\Enums\ShippingCostType $shipping_cost_type = null,
        public ?Carbon $expected_delivery_date = null,
        public ?string $notes = null,
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        // Handle unit_price conversion to Money object
        if ($data['unit_price'] instanceof Money) {
            $unitPrice = $data['unit_price'];
        } elseif (is_numeric($data['unit_price']) && $data['unit_price'] > 0) {
            $currencyCode = $data['currency'] ?? 'USD';
            $unitPrice = Money::of($data['unit_price'], $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
        } else {
            // Default to zero if no valid price provided
            $currencyCode = $data['currency'] ?? 'USD';
            $unitPrice = Money::of(0, $currencyCode);
        }

        return new self(
            product_id: $data['product_id'],
            description: $data['description'],
            quantity: (float) $data['quantity'],
            unit_price: $unitPrice,
            tax_id: $data['tax_id'] ?? null,
            shipping_cost_type: (($data['shipping_cost_type'] ?? null) instanceof \Kezi\Foundation\Enums\ShippingCostType) ? $data['shipping_cost_type'] : (isset($data['shipping_cost_type']) ? \Kezi\Foundation\Enums\ShippingCostType::tryFrom($data['shipping_cost_type']) : null),
            expected_delivery_date: isset($data['expected_delivery_date']) ? Carbon::parse($data['expected_delivery_date']) : null,
            notes: $data['notes'] ?? null,
        );
    }
}
