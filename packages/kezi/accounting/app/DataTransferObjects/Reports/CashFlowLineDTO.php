<?php

namespace Kezi\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class CashFlowLineDTO
{
    public function __construct(
        public ?int $accountId,
        public ?string $accountCode,
        public string $description,
        public Money $amount
    ) {}
}
