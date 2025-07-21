<?php

namespace App\Services;

use App\Exceptions\UpdateNotAllowedException;
use App\Models\JournalEntry;
use App\Rules\ActiveAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function create(array $data): JournalEntry
    {
        Validator::make($data, [
            // Apply the rule to each account_id in the lines array
            'lines.*.account_id' => ['required', 'exists:accounts,id', new ActiveAccount],
            // ... other rules
        ])->validate();

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
        return DB::transaction(function () use ($data, $totalDebit,  $totalCredit) {
            // This is your excellent fix:
            $journalEntry = JournalEntry::create(
                collect($data)->except('lines')->all() + [
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                ]
            );

            $journalEntry->lines()->createMany($data['lines']);

            return $journalEntry;
        });
    }

    public function post(JournalEntry $journalEntry): bool
    {
        // 1. Re-validate the balance before posting.
        $totalDebit = $journalEntry->lines()->sum('debit');
        $totalCredit = $journalEntry->lines()->sum('credit');

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

    public function update(JournalEntry $journalEntry, array $data): bool
    {
        // This is the guard clause. It protects posted entries.
        if ($journalEntry->is_posted) {
            throw new UpdateNotAllowedException('Cannot modify a posted journal entry.');
        }

        // If the guard clause passes, proceed with the update.
        return $journalEntry->update($data);
    }
}
