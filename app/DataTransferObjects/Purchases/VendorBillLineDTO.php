<?php

namespace App\DataTransferObjects\Purchases;

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
        public ?int $asset_category_id = null,
    ) {
    }
}
