<?php

namespace Kezi\Payment\DataTransferObjects\LetterOfCredit;

use Brick\Money\Money;

readonly class CreateLCChargeDTO
{
    public function __construct(
        public int $company_id,
        public int $letter_of_credit_id,
        public int $account_id,
        public int $currency_id,
        public string $charge_type,
        public Money $amount,
        public Money $amount_company_currency,
        public \Illuminate\Support\Carbon $charge_date,
        public ?string $description,
    ) {}
}
