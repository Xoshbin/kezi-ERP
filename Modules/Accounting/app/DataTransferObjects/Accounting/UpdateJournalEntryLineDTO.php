<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

class UpdateJournalEntryLineDTO
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
