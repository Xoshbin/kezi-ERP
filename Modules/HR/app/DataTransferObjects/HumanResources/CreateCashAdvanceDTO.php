<?php

namespace Modules\HR\DataTransferObjects\HumanResources;

use Brick\Money\Money;

readonly class CreateCashAdvanceDTO
{
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public int $currency_id,
        public Money $requested_amount,
        public string $purpose,
        public ?string $expected_return_date = null,
        public ?string $notes = null,
    ) {}
}
