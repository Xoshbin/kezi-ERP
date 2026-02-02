<?php

namespace Kezi\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Carbon\Carbon;

readonly class PartnerLedgerTransactionLineDTO
{
    public function __construct(
        public Carbon $date,
        public string $reference,
        public string $transactionType,
        public Money $debit,
        public Money $credit,
        public Money $balance, // The running balance after this transaction
    ) {}
}
