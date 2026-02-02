<?php

namespace Kezi\HR\DataTransferObjects\HumanResources;

use Brick\Money\Money;

readonly class ExpenseReportLineDTO
{
    public function __construct(
        public int $expense_account_id,
        public string $description,
        public string $expense_date,
        public Money $amount,
        public ?string $receipt_reference = null,
        public ?int $partner_id = null,
    ) {}
}
