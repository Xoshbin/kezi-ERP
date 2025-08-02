<?php

namespace App\Policies;

use App\Enums\Accounting\JournalEntryState;
use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    /**
     * Determine whether the user can reverse the journal entry.
     *
     * @param User $user
     * @param JournalEntry $journalEntry
     * @return bool
     */
    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        // Business rule: Only allow reversal if the journal entry is in Posted state
        return $journalEntry->state === JournalEntryState::Posted;
    }
}
