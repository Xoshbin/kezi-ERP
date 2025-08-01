<?php

namespace App\DataTransferObjects\Accounting;

class CreateBankStatementDTO
{
    /**
     * @param CreateBankStatementLineDTO[] $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $currency_id,
        public readonly int $journal_id,
        public readonly string $reference,
        public readonly string $date,
        public readonly string $starting_balance,
        public readonly string $ending_balance,
        public readonly array $lines,
    ) {}
}
