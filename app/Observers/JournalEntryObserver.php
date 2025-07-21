<?php

namespace App\Observers;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\JournalEntry;

class JournalEntryObserver
{
    /**
     * Handle the JournalEntry "created" event.
     */
    public function created(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Handle the JournalEntry "updating" event. (This is correct)
     */
    public function updating(JournalEntry $journalEntry): void
    {
        // Check if the entry is being posted
        if ($journalEntry->isDirty('is_posted') && $journalEntry->is_posted) {
            // 1. Find the last posted entry for this company to link to.
            $lastEntry = JournalEntry::where('company_id', $journalEntry->company_id)
                ->where('is_posted', true)
                ->latest('entry_date') // Order by date to find the most recent
                ->first();

            // 2. Set the previous_hash on the current entry.
            if ($lastEntry) {
                $journalEntry->previous_hash = $lastEntry->hash;
            }

            // 3. Now, generate the hash for the current entry.
            $journalEntry->hash = $this->generateHashForEntry($journalEntry);
        }
    }



    /**
     * Handle the JournalEntry "updated" event.
     */
    public function updated(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Handle the JournalEntry "deleting" event.
     */
    public function deleting(JournalEntry $journalEntry): bool // Add return type for clarity
    {
        if ($journalEntry->is_posted) {
            // This cleanly stops the deletion process.
            return false;
        }

        // If not posted, allow the deletion to proceed.
        return true;
    }

    /**
     * Handle the JournalEntry "deleted" event.
     */
    public function deleted(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Handle the JournalEntry "restored" event.
     */
    public function restored(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Handle the JournalEntry "force deleted" event.
     */
    public function forceDeleted(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Generate a unique hash for the journal entry's data.
     */
    private function generateHashForEntry(JournalEntry $journalEntry): string
    {
        // IMPORTANT: The previous_hash is now part of the data being hashed.
        $dataToHash = $journalEntry->entry_date .
            $journalEntry->total_debit .
            $journalEntry->lines->toJson() .
            $journalEntry->previous_hash; // This creates the chain link.

        return hash('sha256', $dataToHash);
    }
}
