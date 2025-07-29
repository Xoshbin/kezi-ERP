<?php

namespace App\DataTransferObjects\Accounting;

class CreateJournalEntryDTO
{
    /**
     * @param CreateJournalEntryLineDTO[] $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $journal_id,
        public readonly int $currency_id,
        public readonly string $entry_date,
        public readonly string $reference,
        public readonly ?string $description,
        public readonly int $created_by_user_id,
        public readonly bool $is_posted,
        public readonly array $lines,
    ) {}
}
