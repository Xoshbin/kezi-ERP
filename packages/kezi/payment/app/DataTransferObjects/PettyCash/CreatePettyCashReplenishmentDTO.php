<?php

namespace Kezi\Payment\DataTransferObjects\PettyCash;

use Brick\Money\Money;

readonly class CreatePettyCashReplenishmentDTO
{
    public function __construct(
        public int $company_id,
        public int $fund_id,
        public Money $amount,
        public string $replenishment_date,
        public string $payment_method,
        public ?string $reference = null,
    ) {}
}
