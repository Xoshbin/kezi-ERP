<?php

namespace Modules\Inventory\Services;

use App\Actions\Accounting\CreateJournalEntryForAdjustmentAction; // 1. Import the new action
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Events\AdjustmentDocumentPosted;
use App\Models\AdjustmentDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdjustmentDocumentService
{
    public function __construct(private readonly CreateJournalEntryForAdjustmentAction $createJournalEntryForAdjustmentAction) {}

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

            // 3. Create and execute our new, dedicated action.
            $journalEntry = $this->createJournalEntryForAdjustmentAction->execute($creditNote, $user);

            // Link the created journal entry back to the document.
            $creditNote->journal_entry_id = $journalEntry->id;
            $creditNote->save();

            AdjustmentDocumentPosted::dispatch($creditNote);
        });
    }
}
