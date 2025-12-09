<?php

namespace Modules\Purchase\DataTransferObjects\Purchases;

readonly class CreateVendorBillFromPurchaseOrderDTO
{
    public function __construct(
        public int $purchase_order_id,
        public string $bill_reference,
        public string $bill_date,
        public string $accounting_date,
        public ?string $due_date,
        public int $created_by_user_id,
        public ?int $payment_term_id = null,
        public bool $copy_all_lines = true,
        public ?array $line_quantities = null, // Optional: override quantities for specific lines
    ) {}
}
