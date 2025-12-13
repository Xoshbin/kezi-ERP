<?php

namespace Modules\Accounting\Services;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\Actions\Accounting\ReverseJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Services\CurrencyConverterService;
use Modules\Foundation\Services\SequenceService;

class JournalEntryService
{
    public function __construct(
        protected Accounting\LockDateService $lockDateService,
        protected CurrencyConverterService $currencyConverter,
        protected SequenceService $sequenceService,
    ) {}

    public function post(JournalEntry $journalEntry): bool
    {
        // If the entry is already posted, do nothing and return success.
        if ($journalEntry->is_posted) {
            return true;
        }

        // 1. Re-validate the balance before posting.
        $journalEntry->load('lines', 'currency', 'company.currency');
        // CORRECTED: Sum Money objects using the company's base currency (since line amounts are in base currency)
        $companyCurrencyCode = $journalEntry->company->currency->code;
        $totalDebit = Money::of(0, $companyCurrencyCode);
        $totalCredit = Money::of(0, $companyCurrencyCode);
        foreach ($journalEntry->lines as $line) {
            $totalDebit = $totalDebit->plus($line->debit);
            $totalCredit = $totalCredit->plus($line->credit);
        }

        // MODIFIED: Use isEqualTo() for Money object comparison
        if (! $totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages(['lines' => 'Cannot post an unbalanced entry.']);
        }

        // Add other checks here (lock dates, etc.)

        // 2. Update the totals and post the entry.
        if (empty($journalEntry->entry_number)) {
            $journalEntry->entry_number = $this->sequenceService->getNextJournalEntryNumber($journalEntry->company);
        }
        $journalEntry->total_debit = $totalDebit;
        $journalEntry->total_credit = $totalCredit;
        $journalEntry->is_posted = true;

        return $journalEntry->save();
    }

    /**
     * Deletes a JournalEntry if it is in draft status.
     * Deletion is blocked for posted entries to maintain financial integrity.
     *
     * @param  JournalEntry  $journalEntry  The entry to delete.
     * @return bool|null True on successful deletion.
     *
     * @throws \Modules\Foundation\Exceptions\DeletionNotAllowedException If the entry is already posted.
     * @throws \Modules\Accounting\Exceptions\PeriodIsLockedException If the entry's date is in a locked period.
     */
    public function delete(JournalEntry $journalEntry): ?bool
    {
        // First, check if the entry's date is in a locked period.
        // This applies to ALL entries, whether draft or posted, if their date falls within a locked period.
        $this->lockDateService->enforce($journalEntry->company, Carbon::parse($journalEntry->entry_date));

        // Block deletion if the entry has been posted.
        // Block deletion if the entry has been posted. This is the non-negotiable immutability rule.
        if ($journalEntry->is_posted) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete a posted journal entry. Corrections must be made with a new reversal entry.'
            );
        }

        // Proceed with deletion for draft entries.
        // Using a transaction is good practice, though Eloquent's delete handles this well.
        return DB::transaction(function () use ($journalEntry) {
            // Deleting the JournalEntry will also delete its lines if foreign keys
            // are configured with `onDelete('cascade')`.
            return $journalEntry->delete();
        });
    }

    /**
     * Creates and posts a reversing journal entry for a given posted entry.
     *
     * @param  JournalEntry  $originalEntry  The entry to be reversed.
     * @param  string  $reason  The reason for the reversal.
     * @param  User  $user  The user performing the action.
     * @return JournalEntry The newly created reversing entry.
     *
     * @throws Exception
     */
    public function createReversal(JournalEntry $originalEntry, string $reason, User $user): JournalEntry
    {
        if (! $originalEntry->is_posted) {
            throw new Exception('Only posted journal entries can be reversed.');
        }

        return app(ReverseJournalEntryAction::class)->execute($originalEntry, $reason, $user);
    }

    /**
     * Create a multi-currency journal entry with proper currency conversion.
     * This method handles journal entries where the transaction currency differs from the company base currency.
     * Now uses the CreateJournalEntryAction for consistency.
     *
     * @param  array<string, mixed>  $entryData
     * @param  array<string, mixed>  $lines
     */
    public function createMultiCurrencyEntry(array $entryData, array $lines, Currency $transactionCurrency, User $user): JournalEntry
    {
        // Convert the array-based line data to DTOs
        $lineDTOs = [];
        foreach ($lines as $lineData) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $lineData['account_id'],
                debit: $lineData['debit'] ?? Money::zero($transactionCurrency->code),
                credit: $lineData['credit'] ?? Money::zero($transactionCurrency->code),
                description: $lineData['description'] ?? null,
                partner_id: $lineData['partner_id'] ?? null,
                analytic_account_id: $lineData['analytic_account_id'] ?? null
            );
        }

        // Create the DTO for the journal entry
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $entryData['company_id'],
            journal_id: $entryData['journal_id'],
            currency_id: $transactionCurrency->id,
            entry_date: $entryData['entry_date'],
            reference: $entryData['reference'],
            description: $entryData['description'] ?? null,
            created_by_user_id: $user->id,
            is_posted: $entryData['is_posted'] ?? false,
            lines: $lineDTOs,
            source_type: $entryData['source_type'] ?? null,
            source_id: $entryData['source_id'] ?? null
        );

        // Use the CreateJournalEntryAction which now handles multi-currency
        return app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }
}
