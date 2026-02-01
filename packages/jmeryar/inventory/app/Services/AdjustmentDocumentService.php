<?php

namespace Jmeryar\Inventory\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Contracts\AdjustmentJournalEntryCreatorContract;
use Jmeryar\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;
use Jmeryar\Inventory\Events\AdjustmentDocumentPosted;
use Jmeryar\Inventory\Models\AdjustmentDocument;

class AdjustmentDocumentService
{
    public function __construct(
        private readonly AdjustmentJournalEntryCreatorContract $adjustmentJournalEntryCreator
    ) {}

    /**
     * Post a draft credit note and create its reversing journal entry.
     */
    public function post(AdjustmentDocument $creditNote, User $user): void
    {
        DB::transaction(function () use ($creditNote, $user) {
            // Update the credit note's status and save it.
            $creditNote->status = AdjustmentDocumentStatus::Posted;
            $creditNote->posted_at = now();
            $creditNote->save();

            // Create journal entry using the contract (Event-Driven Architecture)
            $journalEntry = $this->adjustmentJournalEntryCreator->execute($creditNote, $user);

            // Link the created journal entry back to the document.
            $creditNote->journal_entry_id = $journalEntry->id;
            $creditNote->save();

            AdjustmentDocumentPosted::dispatch($creditNote);
        });
    }
}
