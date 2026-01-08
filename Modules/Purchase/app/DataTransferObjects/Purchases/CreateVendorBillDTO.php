<?php

namespace Modules\Purchase\DataTransferObjects\Purchases;

use Modules\Foundation\Enums\Incoterm;

readonly class CreateVendorBillDTO
{
    /**
     * @param  CreateVendorBillLineDTO[]  $lines
     */
    public function __construct(
        public int $company_id,
        public int $vendor_id,
        public int $currency_id,
        public string $bill_reference,
        public string $bill_date,
        public string $accounting_date,
        public ?string $due_date,
        public array $lines,
        public int $created_by_user_id,
        public ?int $payment_term_id = null,
        public ?int $purchase_order_id = null,
        public ?Incoterm $incoterm = null,
    ) {}
}
