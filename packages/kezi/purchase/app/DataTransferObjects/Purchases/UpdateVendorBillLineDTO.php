<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

class UpdateVendorBillLineDTO
{
    public function __construct(
        public readonly string $description,
        public readonly float $quantity,
        public readonly string $unit_price,
        public readonly int $expense_account_id,
        public readonly ?int $product_id,
        public readonly ?int $tax_id,
        public readonly ?int $analytic_account_id,
        public readonly ?\Kezi\Foundation\Enums\ShippingCostType $shipping_cost_type = null,
    ) {}
}
