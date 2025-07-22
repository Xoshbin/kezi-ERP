<?php

namespace App\Services;

use App\Exceptions\UpdateNotAllowedException;
use App\Models\AdjustmentDocument;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdjustmentDocumentService
{
    /**
     * Post a draft credit note and create its reversing journal entry.
     */
    public function post(AdjustmentDocument $creditNote, User $user): void
    {
        if ($creditNote->status !== 'Draft') {
            return; // Or throw an exception
        }

        DB::transaction(function () use ($creditNote, $user) {
            // 1. Update the credit note's status.
            $creditNote->status = 'Posted';
            $creditNote->posted_at = now();

            // 2. Create the reversing journal entry.
            $journalEntry = $this->createJournalEntryForCreditNote($creditNote, $user);
            $creditNote->journal_entry_id = $journalEntry->id;

            $creditNote->save();

            // You could dispatch an event here, like CreditNotePosted::dispatch($creditNote);
        });
    }

    /**
     * Creates the reversing journal entry for a posted credit note.
     */
    private function createJournalEntryForCreditNote(AdjustmentDocument $creditNote, User $user): JournalEntry
    {
        // Get the default accounts from your config.
        $arAccountId = config('accounting.defaults.accounts_receivable_id');
        $incomeAccountId = config('accounting.defaults.default_income_account_id');
        $taxAccountId = config('accounting.defaults.default_tax_account_id');
        $salesJournalId = config('accounting.defaults.sales_journal_id');

        // A sales credit note creates a REVERSE entry of the original sale.
        $lines = [];
        $subtotal = $creditNote->total_amount - $creditNote->total_tax;

        // 1. Debit Revenue/Income to reduce it.
        $lines[] = [ 'account_id' => $incomeAccountId, 'debit' => $subtotal ];
        // 2. Debit Tax Payable to reduce it.
        $lines[] = [ 'account_id' => $taxAccountId, 'debit' => $creditNote->total_tax ];
        // 3. Credit Accounts Receivable to reduce the customer's debt.
        $lines[] = [ 'account_id' => $arAccountId, 'credit' => $creditNote->total_amount ];

        $journalEntryData = [
            'company_id' => $creditNote->company_id,
            'journal_id' => $salesJournalId,
            'entry_date' => $creditNote->posted_at,
            'reference' => 'CN-' . $creditNote->reference_number,
            'description' => 'Credit Note ' . $creditNote->reference_number,
            'source_type' => AdjustmentDocument::class,
            'source_id' => $creditNote->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        return (new JournalEntryService())->create($journalEntryData);
    }
    public function update(AdjustmentDocument $creditNote, array $data): bool
    {
        // Block any attempt to change a posted document.
        if ($creditNote->status === 'Posted') {
            throw new UpdateNotAllowedException('Cannot modify a posted credit note.');
        }

        // ... logic to update a draft credit note ...
    }

}
