<?php

namespace Kezi\Payment\DataTransferObjects\LetterOfCredit;

use Brick\Money\Money;

readonly class UtilizeLCDTO
{
    public function __construct(
        public int $vendor_bill_id,
        public Money $utilized_amount,
        public Money $utilized_amount_company_currency,
        public \Illuminate\Support\Carbon $utilization_date,
    ) {}
}
