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
}
