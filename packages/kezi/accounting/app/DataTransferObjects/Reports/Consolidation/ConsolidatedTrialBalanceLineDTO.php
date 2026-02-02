<?php

namespace Kezi\Accounting\DataTransferObjects\Reports\Consolidation;

use Brick\Money\Money;
use Kezi\Accounting\Enums\Accounting\AccountType;

readonly class ConsolidatedTrialBalanceLineDTO
{
    /**
     * @param  array<int, Money>  $companyBalances  Keyed by Company ID, value in Parent Currency
     */
    public function __construct(
        public string $accountCode,
        public string $accountName,
        public AccountType $accountType,
        public Money $consolidatedDebit,
        public Money $consolidatedCredit,
        public Money $eliminationDebit,
        public Money $eliminationCredit,
        public array $companyBalances = [],
    ) {}
}
