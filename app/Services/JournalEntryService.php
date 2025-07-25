<?php

namespace App\Services;

use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Rules\ActiveAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function create(array $data, bool $postImmediately = false): JournalEntry
    {
        $this->checkIfPeriodIsLocked($data['company_id'], $data['entry_date']);

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
        $totalDebit = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        // 2. Validate the balance rule
        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            // This stops execution and throws the clean error your test expects.
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.'
            ]);
        }

        // 3. Create within a Transaction
        return DB::transaction(function () use ($data, $totalDebit,  $totalCredit, $postImmediately) {
            // This is your excellent fix:
            $journalEntry = JournalEntry::create(
                collect($data)->except('lines')->all() + [
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'is_posted' => $postImmediately, // <-- Set is_posted
                ]
            );

            foreach ($data['lines'] as $lineData) {
                $journalEntry->lines()->create($lineData);
            }

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
        // By operating on the collection ($journalEntry->lines) instead of the query builder,
        // we ensure that the model's accessors (and thus the MoneyCast) are used,
        // providing the correct float values for the sum.
        $journalEntry->load('lines');
        $totalDebit = $journalEntry->lines->sum('debit');
        $totalCredit = $journalEntry->lines->sum('credit');

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages(['lines' => 'Cannot post an unbalanced entry.']);
        }

        // Add other checks here (lock dates, etc.)

        // 2. Update the totals and post the entry.
        $journalEntry->total_debit = $totalDebit;
        $journalEntry->total_credit = $totalCredit;
        $journalEntry->is_posted = true;

        return $journalEntry->save();
    }

    public function update(JournalEntry $journalEntry, array $data): JournalEntry
    {
        // 1. First, check if the original entry's date is locked.
        $this->checkIfPeriodIsLocked($journalEntry->company_id, $journalEntry->entry_date);

        // Also check on update if the date is being changed.
        if (isset($data['entry_date'])) {
            $this->checkIfPeriodIsLocked($journalEntry->company_id, $data['entry_date']);
        }

        // This is the guard clause. It protects posted entries.
        if ($journalEntry->is_posted) {
            throw new UpdateNotAllowedException('Cannot modify a posted journal entry.');
        }

        // If the guard clause passes, proceed with the update.
        $journalEntry->update($data);
        return $journalEntry;
    }

    /**
     * Checks if a given date for a company falls within a locked period.
     */
    private function checkIfPeriodIsLocked(int $companyId, string $date): void
    {
        $entryDate = Carbon::parse($date);

        $lockDate = LockDate::where('company_id', $companyId)->first();

        if ($lockDate && $entryDate->lte($lockDate->locked_until)) {
            throw new PeriodIsLockedException('The accounting period is locked and cannot be modified.');
        }
    }
}
