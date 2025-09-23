<?php

namespace App\DataTransferObjects\Purchases;

use Brick\Money\Money;
use Carbon\Carbon;

/**
 * Data Transfer Object for creating a Purchase Order Line
 */
readonly class CreatePurchaseOrderLineDTO
{
    /**
     * @param int $product_id
     * @param string $description
     * @param float $quantity
     * @param Money $unit_price
     * @param int|null $tax_id
     * @param Carbon|null $expected_delivery_date
     * @param string|null $notes
     */
    public function __construct(
        public int $product_id,
        public string $description,
        public float $quantity,
        public Money $unit_price,
        public ?int $tax_id = null,
        public ?Carbon $expected_delivery_date = null,
        public ?string $notes = null,
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $unitPrice = $data['unit_price'] instanceof Money 
            ? $data['unit_price'] 
            : Money::of($data['unit_price'], $data['currency'] ?? 'USD');

        return new self(
            product_id: $data['product_id'],
            description: $data['description'],
            quantity: (float) $data['quantity'],
            unit_price: $unitPrice,
            tax_id: $data['tax_id'] ?? null,
            expected_delivery_date: isset($data['expected_delivery_date']) ? Carbon::parse($data['expected_delivery_date']) : null,
            notes: $data['notes'] ?? null,
        );
    }
}
