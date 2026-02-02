<?php

namespace Kezi\Accounting\Observers;

use Kezi\Accounting\Models\JournalEntry;

class JournalEntryObserver
{
    /**
     * Handle the JournalEntry "creating" event.
     * This handles cases where an entry is created as already posted.
     */
    public function creating(JournalEntry $journalEntry): void
    {
        if ($journalEntry->is_posted) {
            app(\Kezi\Accounting\Services\Accounting\LockDateService::class)->enforce($journalEntry->company, $journalEntry->entry_date);
            $this->applyHashingAndLinking($journalEntry);
        }
    }

    /**
     * Handle the JournalEntry "updating" event.
     * This handles cases where a draft entry is transitioned to posted.
     */
    public function updating(JournalEntry $journalEntry): void
    {
        if ($journalEntry->isDirty('is_posted') && $journalEntry->is_posted) {
            app(\Kezi\Accounting\Services\Accounting\LockDateService::class)->enforce($journalEntry->company, $journalEntry->entry_date);
            $this->applyHashingAndLinking($journalEntry);
        }
    }

    /**
     * Handle the JournalEntry "deleting" event.
     */
    public function deleting(JournalEntry $journalEntry): bool
    {
        if ($journalEntry->is_posted) {
            return false;
        }

        return true;
    }

    /**
     * Applies the hashing and linking logic to a journal entry.
     */
    private function applyHashingAndLinking(JournalEntry $journalEntry): void
    {
        // 1. Find the last posted entry for this company to link to.
        $lastEntry = JournalEntry::where('company_id', $journalEntry->company_id)
            ->where('is_posted', true)
            ->where('id', '!=', $journalEntry->id) // Exclude the entry being saved
            ->orderByDesc('entry_date')           // Order by date to find the most recent
            ->orderByDesc('id')                   // Add secondary sort for robustness
            ->first();

        // 2. Set the previous_hash on the current entry.
        if ($lastEntry) {
            $journalEntry->previous_hash = $lastEntry->hash;
        }

        // 3. Now, generate the hash for the current entry.
        $journalEntry->hash = $this->generateHashForEntry($journalEntry);
    }

    /**
     * Generate a unique hash for the journal entry's data.
     */
    private function generateHashForEntry(JournalEntry $journalEntry): string
    {
        // IMPORTANT: The previous_hash is now part of the data being hashed.
        $dataToHash = $journalEntry->entry_date.
            $journalEntry->total_debit.
            $journalEntry->lines->toJson().
            $journalEntry->previous_hash; // This creates the chain link.

        return hash('sha256', $dataToHash);
    }
}
