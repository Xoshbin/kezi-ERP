<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class ReportLineDTO
{
    public function __construct(
        public ?int $accountId,
        public string $accountCode,
        public string $accountName,
        public Money $balance,
    ) {}
}
