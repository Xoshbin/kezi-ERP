<?php

namespace App\DataTransferObjects\Accounting;

use Brick\Money\Money;

class CreateJournalEntryLineDTO
{
    public function __construct(
        public readonly int $account_id,
        public readonly Money $debit,
        public readonly Money $credit,
        public readonly ?string $description,
        public readonly ?int $partner_id,
        public readonly ?int $analytic_account_id,
    ) {}
}
