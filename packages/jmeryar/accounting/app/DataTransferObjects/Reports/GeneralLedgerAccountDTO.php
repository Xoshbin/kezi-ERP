<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class GeneralLedgerAccountDTO
{
    /**
     * @param  Collection<int, GeneralLedgerTransactionLineDTO>  $transactionLines
     */
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public Money $openingBalance,
        public Collection $transactionLines,
        public Money $closingBalance,
    ) {}
}
