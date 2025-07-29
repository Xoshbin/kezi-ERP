<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\Models\Currency;
use App\Models\JournalEntry;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJournalEntryAction
{
    public function execute(CreateJournalEntryDTO $dto): JournalEntry
    {
        // These variables are defined in the outer scope
        $totalDebit = collect($dto->lines)->sum(fn($line) => $line->debit);
        $totalCredit = collect($dto->lines)->sum(fn($line) => $line->credit);

        if (bccomp((string)$totalDebit, (string)$totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // --- FIX IS HERE: Add $totalDebit and $totalCredit to the 'use' statement ---
        return DB::transaction(function () use ($dto, $totalDebit, $totalCredit) {
            $currency = Currency::find($dto->currency_id);
            if (!$currency) {
                throw new \Exception("Currency with ID {$dto->currency_id} not found.");
            }
            $currencyCode = $currency->code;

            // Now these variables are available inside the closure
            $journalEntry = JournalEntry::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'created_by_user_id' => $dto->created_by_user_id,
                'is_posted' => $dto->is_posted,
                'total_debit' => Money::of($totalDebit, $currencyCode),
                'total_credit' => Money::of($totalCredit, $currencyCode),
            ]);

            // ... (rest of the code is correct) ...

            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'account_id' => $lineDto->account_id,
                    'partner_id' => $lineDto->partner_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                    'description' => $lineDto->description,
                    'debit' => Money::of($lineDto->debit, $currencyCode),
                    'credit' => Money::of($lineDto->credit, $currencyCode),
                    'currency_id' => $dto->currency_id,
                ];
            }

            if (!empty($linesToCreate)) {
                $journalEntry->lines()->createMany($linesToCreate);
            }

            return $journalEntry;
        });
    }
}
