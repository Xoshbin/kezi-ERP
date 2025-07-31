<?php

namespace App\DataTransferObjects\Accounting;

class CreateJournalEntryLineDTO
{
    public function __construct(
        public readonly int $account_id,
        public readonly string $debit,
        public readonly string $credit,
        public readonly ?string $description,
        public readonly ?int $partner_id,
        public readonly ?int $analytic_account_id,
    ) {}
}
