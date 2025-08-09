<?php

namespace App\Enums\Accounting;

enum JournalEntryState: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    /**
     * Get the translated label for the journal entry state.
     */
    public function label(): string
    {
        return __('enums.journal_entry_state.' . $this->value);
    }
}
