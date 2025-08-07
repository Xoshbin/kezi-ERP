<?php

namespace App\DataTransferObjects\Accounting;

class CreateJournalEntryLineDTO
{
    public function __construct(
        public readonly int $account_id,
        public readonly \Brick\Money\Money $debit,
        public readonly \Brick\Money\Money $credit,
        public readonly ?string $description,
        public readonly ?int $partner_id,
        public readonly ?int $analytic_account_id,
    ) {}
}
