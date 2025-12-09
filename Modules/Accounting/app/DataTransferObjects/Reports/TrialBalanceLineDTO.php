<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class TrialBalanceLineDTO
{
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public \Modules\Accounting\Enums\Accounting\AccountType $accountType,
        public Money $debit,
        public Money $credit,
    ) {
    }
}
