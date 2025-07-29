<?php

namespace App\Services;

use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Rules\ActiveAccount;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function __construct(protected AccountingValidationService $accountingValidationService)
    {
    }

    public function create(array $data, bool $postImmediately = false): JournalEntry
    {
        $this->accountingValidationService->checkIfPeriodIsLocked($data['company_id'], $data['entry_date']);

        Validator::make($data, [
            // Apply the rule to each account_id in the lines array
            'lines.*.account_id' => ['required', 'exists:accounts,id', new ActiveAccount],
            // ... other rules
        ])->validate();

        // IF a currency_id is not specified, use the company's default currency.
        if (empty($data['currency_id'])) {
            $company = Company::find($data['company_id']);
            $data['currency_id'] = $company->currency_id;
        }

        // 1. Calculate Totals
        // MODIFIED: Use Money objects for precise summation
        $currencyCode = \App\Models\Currency::find($data['currency_id'])->code;
        $totalDebit = Money::of(0, $currencyCode);
        $totalCredit = Money::of(0, $currencyCode);

        foreach ($data['lines'] as $line) {
            if (isset($line['debit'])) {
                $totalDebit = $totalDebit->plus($line['debit']);
            }
            if (isset($line['credit'])) {
                $totalCredit = $totalCredit->plus($line['credit']);
            }
        }

        // 2. Validate the balance rule
        // MODIFIED: Use isEqualTo() for Money object comparison
        if (!$totalDebit->isEqualTo($totalCredit)) {
            // This stops execution and throws the clean error your test expects.
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.'
            ]);
        }

        // 3. Create within a Transaction
        return DB::transaction(function () use ($data, $postImmediately, $totalDebit, $totalCredit) {
            $journalEntry = JournalEntry::create(
                collect($data)->except('lines')->all() + [
                    'is_posted' => $postImmediately,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                ]
            );

            foreach ($data['lines'] as $lineData) {
                $journalEntry->lines()->create($lineData);
            }

            // The totals are now set correctly on creation.
            // We can return the entry directly.
            return $journalEntry;
        });
    }

    public function post(JournalEntry $journalEntry): bool
    {
        // If the entry is already posted, do nothing and return success.
        if ($journalEntry->is_posted) {
            return true;
        }

        // 1. Re-validate the balance before posting.
        $journalEntry->load('lines', 'currency');
        // MODIFIED: Sum Money objects from the loaded relations.
        $totalDebit = Money::of(0, $journalEntry->currency->code);
        $totalCredit = Money::of(0, $journalEntry->currency->code);
        foreach($journalEntry->lines as $line) {
            $totalDebit = $totalDebit->plus($line->debit);
            $totalCredit = $totalCredit->plus($line->credit);
        }

        // MODIFIED: Use isEqualTo() for Money object comparison
        if (!$totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages(['lines' => 'Cannot post an unbalanced entry.']);
        }

        // Add other checks here (lock dates, etc.)

        // 2. Update the totals and post the entry.
        $journalEntry->total_debit = $totalDebit;
        $journalEntry->total_credit = $totalCredit;
        $journalEntry->is_posted = true;

        return $journalEntry->save();
    }

    /**
     * Deletes a JournalEntry if it is in draft status.
     * Deletion is blocked for posted entries to maintain financial integrity.
     *
     * @param JournalEntry $journalEntry The entry to delete.
     * @return bool|null True on successful deletion.
     * @throws DeletionNotAllowedException If the entry is already posted.
     * @throws PeriodIsLockedException If the entry's date is in a locked period.
     */
    public function delete(JournalEntry $journalEntry): ?bool
    {
        // First, check if the entry's date is in a locked period.
        // This applies to ALL entries, whether draft or posted, if their date falls within a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($journalEntry->company_id, $journalEntry->entry_date);

        // Block deletion if the entry has been posted.
        // Block deletion if the entry has been posted. This is the non-negotiable immutability rule.
        if ($journalEntry->is_posted) {
            throw new DeletionNotAllowedException(
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
}
