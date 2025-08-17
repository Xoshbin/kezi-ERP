<?php

namespace App\Actions\Accounting;

use App\Models\User;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Enums\Accounting\JournalEntryState;
use App\Models\BankStatementLine;
use App\Models\JournalEntry;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReverseJournalEntryAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(JournalEntry $journalEntry, string $reason, User $user): JournalEntry
    {
        return DB::transaction(function () use ($journalEntry, $reason, $user) {
            // Guard Clause: Check if the journal entry is already reversed (idempotent)
            if ($journalEntry->state === JournalEntryState::Reversed) {
                // If so, find and return the existing reversal entry to maintain idempotency.
                return JournalEntry::where('reversed_entry_id', $journalEntry->id)->firstOrFail();
            }

            $currencyCode = $journalEntry->currency->code;

            // Create reversing journal entry lines with inverted debit/credit amounts
            $reversingLines = [];
            foreach ($journalEntry->lines as $line) {
                $reversingLines[] = new CreateJournalEntryLineDTO(
                    account_id: $line->account_id,
                    debit: Money::ofMinor($line->credit->getMinorAmount()->toInt(), $currencyCode), // Invert: credit becomes debit
                    credit: Money::ofMinor($line->debit->getMinorAmount()->toInt(), $currencyCode),  // Invert: debit becomes credit
                    description: $line->description,
                    partner_id: $line->partner_id,
                    analytic_account_id: $line->analytic_account_id,
                );
            }

            // Create the reversing journal entry
            $reversingEntryDTO = new CreateJournalEntryDTO(
                company_id: $journalEntry->company_id,
                journal_id: $journalEntry->journal_id,
                currency_id: $journalEntry->currency_id,
                entry_date: now()->format('Y-m-d'),
                reference: 'REV/' . $journalEntry->reference,
                description: $reason,
                created_by_user_id: $user->id,
                is_posted: true, // Reversing entries are posted immediately
                lines: $reversingLines,
                source_type: null,
                source_id: null,
            );

            // Set the reversed_entry_id on the reversing entry
            $reversingEntry = $this->createJournalEntryAction->execute($reversingEntryDTO);
            $reversingEntry->reversed_entry_id = $journalEntry->id;
            $reversingEntry->save();
            // Update the original journal entry state to Reversed
            $journalEntry->state = JournalEntryState::Reversed;
            $journalEntry->reversed_entry_id = $reversingEntry->id;
            $journalEntry->save();

            // Update source record if it's a BankStatementLine
            if ($journalEntry->source_type === BankStatementLine::class && $journalEntry->source_id) {
                $bankStatementLine = BankStatementLine::find($journalEntry->source_id);
                if ($bankStatementLine) {
                    $bankStatementLine->is_reconciled = false;
                    $bankStatementLine->save();
                }
            }

            return $reversingEntry;
        });
    }
}
