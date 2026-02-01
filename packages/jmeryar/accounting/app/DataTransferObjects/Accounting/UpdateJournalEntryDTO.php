<?php

namespace Jmeryar\Accounting\DataTransferObjects\Accounting;

use Jmeryar\Accounting\Models\JournalEntry;

class UpdateJournalEntryDTO
{
    /**
     * @param  array<int, UpdateJournalEntryLineDTO>  $lines
     */
    public function __construct(
        public readonly JournalEntry $journalEntry,
        public readonly int $journal_id,
        public readonly int $currency_id,
        public readonly string $entry_date,
        public readonly ?string $reference,
        public readonly ?string $description,
        public readonly bool $is_posted,
        public readonly array $lines,
    ) {}
}
