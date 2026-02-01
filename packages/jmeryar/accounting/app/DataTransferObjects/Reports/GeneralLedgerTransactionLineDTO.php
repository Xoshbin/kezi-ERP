<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Carbon\Carbon;

readonly class GeneralLedgerTransactionLineDTO
{
    public function __construct(
        public int $journalEntryId,
        public Carbon $date,
        public ?string $reference,
        public ?string $description,
        public ?string $contraAccount,
        public Money $debit,
        public Money $credit,
        public Money $balance, // The running balance after this transaction
    ) {}
}
