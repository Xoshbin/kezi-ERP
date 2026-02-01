<?php

namespace Kezi\Accounting\DataTransferObjects\Accounting;

use Brick\Money\Money;

class ApplyWithholdingTaxDTO
{
    public function __construct(
        public readonly int $company_id,
        public readonly int $payment_id,
        public readonly int $vendor_id,
        public readonly int $withholding_tax_type_id,
        public readonly Money $base_amount, // The gross amount subject to WHT
        public readonly int $currency_id,
    ) {}
}
