<?php

namespace Jmeryar\Accounting\Policies;

use App\Models\User;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Models\JournalEntry;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_journal_entry');
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('view_journal_entry');
    }

    public function create(User $user): bool
    {
        return $user->can('create_journal_entry');
    }

    public function update(User $user, JournalEntry $journalEntry): bool
    {
        // Enforce RBAC and Immutability
        return $user->can('update_journal_entry') && ! $journalEntry->is_posted;
    }

    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        // Enforce RBAC and Immutability
        return $user->can('delete_journal_entry') && ! $journalEntry->is_posted;
    }

    /**
     * Determine whether the user can reverse the journal entry.
     */
    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        // Business rule: Only allow reversal if the journal entry is in Posted state
        return $user->can('reverse_journal_entry') && $journalEntry->state === JournalEntryState::Posted;
    }
}
