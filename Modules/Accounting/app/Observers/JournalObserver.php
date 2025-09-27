<?php

namespace Modules\Accounting\Observers;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\Journal;

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
