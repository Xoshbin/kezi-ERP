<?php

namespace App\DataTransferObjects\Purchases;

class CreateVendorBillDTO
{
    /**
     * @param CreateVendorBillLineDTO[] $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $vendor_id,
        public readonly int $currency_id,
        public readonly string $bill_reference,
        public readonly string $bill_date,
        public readonly string $accounting_date,
        public readonly ?string $due_date,
        public readonly array $lines,
    ) {}
}
