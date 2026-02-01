<?php

namespace Jmeryar\Accounting\Observers;

use Jmeryar\Accounting\Models\Journal;

class JournalObserver
{
    /**
     * Handle the Journal "deleting" event.
     * Prevents deletion if the journal has associated entries.
     */
    public function deleting(Journal $journal): void
    {
        if ($journal->journalEntries()->exists()) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException('Cannot delete a journal with associated journal entries.');
        }
    }
}
