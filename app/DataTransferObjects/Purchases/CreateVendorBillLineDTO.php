<?php

namespace App\DataTransferObjects\Purchases;

use Brick\Money\Money;

class CreateVendorBillLineDTO
{
    /**
     * @param string|Money $unit_price This can be a string or a Money object.
     */
    public function __construct(
        // CORRECTED: product_id must be nullable to allow for service/description-only lines.
        public readonly ?int $product_id,
        public readonly string $description,
        public readonly int $quantity,
        public readonly string|Money $unit_price,
        public readonly int $expense_account_id,
        public readonly ?int $tax_id,
        public readonly ?int $analytic_account_id,
        public readonly ?int $asset_category_id = null,
        public readonly ?string $currency = null,
    ) {}
}
