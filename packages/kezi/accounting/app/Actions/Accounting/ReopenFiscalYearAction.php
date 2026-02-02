<?php

namespace Kezi\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Enums\Accounting\FiscalYearState;
use Kezi\Accounting\Exceptions\FiscalYearCannotBeReopenedException;
use Kezi\Accounting\Models\FiscalYear;
use Kezi\Accounting\Models\JournalEntry;

class ReopenFiscalYearAction
{
    public function __construct(
        private readonly ReverseJournalEntryAction $reverseJournalEntryAction,
    ) {}

    /**
     * Execute the action to reopen a closed fiscal year.
     *
     * This reverses the closing journal entry and sets the fiscal year back to open state.
     *
     * @throws FiscalYearCannotBeReopenedException
     */
    public function execute(FiscalYear $fiscalYear, int $userId): FiscalYear
    {
        return DB::transaction(function () use ($fiscalYear, $userId) {
            // Validate fiscal year can be reopened
            $this->validateCanReopen($fiscalYear);

            // Reverse the closing journal entry if it exists
            if ($fiscalYear->closing_journal_entry_id) {
                $closingEntry = $fiscalYear->closingJournalEntry;
                if ($closingEntry) {
                    $user = \App\Models\User::findOrFail($userId);
                    $this->reverseJournalEntryAction->execute(
                        $closingEntry,
                        __('accounting::fiscal_year.reopen_reversal_reason', ['name' => $fiscalYear->name]),
                        $user
                    );
                }
            }

            // Update fiscal year state
            $fiscalYear->update([
                'state' => FiscalYearState::Open,
                'closing_journal_entry_id' => null,
                'closed_by_user_id' => null,
                'closed_at' => null,
            ]);

            return $fiscalYear->refresh();
        });
    }

    /**
     * Validate that the fiscal year can be reopened.
     *
     * @throws FiscalYearCannotBeReopenedException
     */
    private function validateCanReopen(FiscalYear $fiscalYear): void
    {
        if (! $fiscalYear->canReopen()) {
            throw new FiscalYearCannotBeReopenedException(
                'Fiscal year cannot be reopened. It must be in closed state.'
            );
        }

        // Check if there are any transactions in subsequent fiscal years
        $hasSubsequentTransactions = JournalEntry::where('company_id', $fiscalYear->company_id)
            ->where('entry_date', '>', $fiscalYear->end_date)
            ->where('state', 'posted')
            ->exists();

        if ($hasSubsequentTransactions) {
            throw new FiscalYearCannotBeReopenedException(
                'Fiscal year cannot be reopened because there are posted transactions after its end date.'
            );
        }
    }
}
