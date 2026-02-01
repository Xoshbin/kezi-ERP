<?php

namespace Jmeryar\Purchase\DataTransferObjects\Purchases;

use Brick\Money\Money;

readonly class VendorBillLineDTO
{
    public function __construct(
        public ?int $product_id,
        public string $description,
        public int $quantity,
        public Money $unit_price,
        public ?int $tax_id,
        public int $expense_account_id,
        public ?int $analytic_account_id,
        public ?\Jmeryar\Foundation\Enums\ShippingCostType $shipping_cost_type = null,
        public ?int $asset_category_id = null,
        public ?string $deferred_start_date = null,
        public ?string $deferred_end_date = null,
    ) {}
}
