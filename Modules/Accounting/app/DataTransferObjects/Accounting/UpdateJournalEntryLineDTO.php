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
        public readonly ?string $original_currency_amount = null,
        public readonly ?float $exchange_rate_at_transaction = null,
    ) {}
}
