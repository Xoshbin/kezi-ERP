<?php

namespace App\DataTransferObjects\Purchases;

class CreateVendorBillLineDTO
{
    public function __construct(
    // We keep all parameters to support both services and products
    public readonly float $quantity,
    public readonly string $unit_price,
    public readonly ?int $product_id = null,
    public readonly ?string $description = null,
    public readonly ?int $expense_account_id = null,
    public readonly ?int $tax_id = null,
    public readonly ?int $analytic_account_id = null,
) {}
}
