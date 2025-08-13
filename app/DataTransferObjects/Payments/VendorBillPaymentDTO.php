<?php

namespace App\DataTransferObjects\Payments;

use Brick\Money\Money;

readonly class VendorBillPaymentDTO
{
    public function __construct(
        public int $vendor_bill_id,
        public Money $amount_applied,
    ) {}
}
