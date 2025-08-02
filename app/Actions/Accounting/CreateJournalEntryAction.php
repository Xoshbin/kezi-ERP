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
        $currency = Currency::find($dto->currency_id);
        if (!$currency) {
            throw new \Exception("Currency with ID {$dto->currency_id} not found.");
        }
        $currencyCode = $currency->code;

        $totalDebit = Money::zero($currencyCode);
        $totalCredit = Money::zero($currencyCode);

        foreach ($dto->lines as $line) {
            $totalDebit = $totalDebit->plus($line->debit);
            $totalCredit = $totalCredit->plus($line->credit);
        }

        if (!$totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // --- FIX IS HERE: Add $totalDebit and $totalCredit to the 'use' statement ---
        return DB::transaction(function () use ($dto, $totalDebit, $totalCredit, $currencyCode) {
            $journalEntry = JournalEntry::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'created_by_user_id' => $dto->created_by_user_id,
                'is_posted' => $dto->is_posted,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'source_type' => $dto->source_type,
                'source_id' => $dto->source_id,
            ]);

            // ... (rest of the code is correct) ...

            foreach ($dto->lines as $lineDto) {
                $journalEntry->lines()->create([
                    'account_id' => $lineDto->account_id,
                    'partner_id' => $lineDto->partner_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                    'description' => $lineDto->description,
                    'debit' => $lineDto->debit,
                    'credit' => $lineDto->credit,
                ]);
            }

            return $journalEntry;
        });
    }
}
