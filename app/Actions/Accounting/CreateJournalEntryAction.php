<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJournalEntryAction
{
    public function __construct(private readonly LockDateService $lockDateService)
    {
    }

    public function execute(CreateJournalEntryDTO $dto): JournalEntry
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->entry_date));

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
        return DB::transaction(function () use ($dto, $totalDebit, $totalCredit, $currencyCode, $currency) {
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
                $multiplier = pow(10, $currency->decimal_places);
                $debit_in_minor_units = (int) ($lineDto->debit->getAmount()->toFloat() * $multiplier);
                $credit_in_minor_units = (int) ($lineDto->credit->getAmount()->toFloat() * $multiplier);

                $journalEntry->lines()->create([
                    'account_id' => $lineDto->account_id,
                    'partner_id' => $lineDto->partner_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                    'description' => $lineDto->description,
                    'debit' => $debit_in_minor_units,
                    'credit' => $credit_in_minor_units,
                ]);
            }

            return $journalEntry;
        });
    }
}
