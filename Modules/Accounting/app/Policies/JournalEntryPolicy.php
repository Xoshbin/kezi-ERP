<?php

namespace Modules\Accounting\Policies;

use App\Enums\Accounting\JournalEntryState;
use App\Models\User;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, JournalEntry $journalEntry): bool
    {
        // Original logic: Prevent editing of posted journal entries.
        // return !$journalEntry->is_posted;
        return true;
    }

    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        return ! $journalEntry->is_posted;
    }

    /**
     * Determine whether the user can reverse the journal entry.
     */
    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        // Business rule: Only allow reversal if the journal entry is in Posted state
        return $journalEntry->state === JournalEntryState::Posted;
    }
}
