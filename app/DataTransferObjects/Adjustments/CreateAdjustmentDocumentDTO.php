<?php

namespace App\DataTransferObjects\Adjustments;

class CreateAdjustmentDocumentDTO
{
    public function __construct(
        public readonly int $company_id,
        public readonly string $type,
        public readonly string $date,
        public readonly string $reference_number,
        public readonly string $total_amount,
        public readonly string $total_tax,
        public readonly string $reason,
        public readonly int $currency_id,
        public readonly ?int $original_invoice_id,
        public readonly ?int $original_vendor_bill_id,
    ) {}
}
