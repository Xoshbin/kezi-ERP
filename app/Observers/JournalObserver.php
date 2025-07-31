<?php

namespace App\Observers;

use App\Models\Journal;
use App\Exceptions\DeletionNotAllowedException;

class JournalObserver
{
    /**
     * Handle the Journal "deleting" event.
     * Prevents deletion if the journal has associated entries.
     */
    public function deleting(Journal $journal): void
    {
        if ($journal->journalEntries()->exists()) {
            throw new DeletionNotAllowedException('Cannot delete a journal with associated journal entries.');
        }
    }
}
