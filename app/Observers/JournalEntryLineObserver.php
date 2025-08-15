<?php

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

class JournalEntryLineObserver
{
    /**
     * Handle the JournalEntryLine "creating" event.
     *
     * @param JournalEntryLine $journalEntryLine
     * @return void
     */
    public function creating(JournalEntryLine $journalEntryLine): void
    {
        // If the 'journalEntry' relationship is not already loaded, but the
        // foreign key 'journal_entry_id' exists on the model instance,
        // we manually set the relationship. This is the crucial step that
        // provides the context needed by the MoneyCast just before the model is saved.
        if (!$journalEntryLine->relationLoaded('journalEntry') && $journalEntryLine->journal_entry_id) {
            $journalEntry = JournalEntry::find($journalEntryLine->journal_entry_id);
            if ($journalEntry) {
                $journalEntryLine->setRelation('journalEntry', $journalEntry);
            }
        }
    }
}
