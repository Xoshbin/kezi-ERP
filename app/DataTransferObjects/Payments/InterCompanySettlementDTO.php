<?php

namespace App\DataTransferObjects\Payments;

use Brick\Money\Money;

readonly class InterCompanySettlementDTO
{
    public function __construct(
        public int $paying_company_id,
        public int $receiving_company_id,
        public Money $settlement_amount,
        public string $settlement_date,
        public ?string $reference = null,
        public ?string $description = null,
    ) {}
}
