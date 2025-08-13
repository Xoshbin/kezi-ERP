<?php

namespace App\DataTransferObjects\Sales;

use App\Models\Invoice;
use App\Models\VendorBill;

readonly class RecurringInvoiceResultDTO
{
    public function __construct(
        public Invoice $invoice,
        public VendorBill $vendorBill,
        public string $reference,
        public bool $success = true,
        public ?string $error_message = null,
    ) {}
}
