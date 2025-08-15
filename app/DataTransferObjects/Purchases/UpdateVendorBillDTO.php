<?php

namespace App\DataTransferObjects\Purchases;

use App\Models\VendorBill;

readonly class UpdateVendorBillDTO
{
    /**
     * @param VendorBillLineDTO[] $lines
     */
    public function __construct(
        public VendorBill $vendorBill,
        public int $company_id,
        public int $vendor_id,
        public int $currency_id,
        public string $bill_reference,
        public string $bill_date,
        public string $accounting_date,
        public ?string $due_date,
        public array $lines,
        public int $updated_by_user_id
    ) {
    }
}