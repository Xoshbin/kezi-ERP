<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\Enums\Accounting\JournalEntryState;
use App\Models\Company;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\Account;
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

        foreach ($dto->lines as $index => $line) {
            $account = Account::find($line->account_id);
            if ($account && $account->is_deprecated) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => "Account '{$account->name}' is deprecated and cannot be used.",
                ]);
            }
            $totalDebit = $totalDebit->plus($line->debit);
            $totalCredit = $totalCredit->plus($line->credit);
        }

        if (!$totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // --- FIX IS HERE: Add $totalDebit and $totalCredit to the 'use' statement ---
        return DB::transaction(function () use ($dto, $totalDebit, $totalCredit) {
            $journalEntry = JournalEntry::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'created_by_user_id' => $dto->created_by_user_id,
                'is_posted' => $dto->is_posted,
                'state' => $dto->is_posted ? JournalEntryState::Posted : JournalEntryState::Draft, // Set correct state based on is_posted
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'source_type' => $dto->source_type,
                'source_id' => $dto->source_id,
            ]);

            // This ensures the $journalEntry object is fully hydrated before we use it.
            $journalEntry = $journalEntry->fresh()->load('currency');

            foreach ($dto->lines as $lineDto) {
                $line = new \App\Models\JournalEntryLine();

                // First, establish the relationship. This makes the parent's context (like currency)
                // available to the line model *before* any attributes are set. This is the key
                // to solving the MoneyCast issue without schema changes.
                $line->journalEntry()->associate($journalEntry);

                // Now, fill the attributes. The MoneyCast on 'debit' and 'credit' will be
                // triggered here, but it can now successfully call getCurrencyIdAttribute()
                // because the journalEntry relationship is established.

                // Determine the original currency amount and currency ID
                $originalAmount = $lineDto->original_currency_amount;
                $originalCurrencyId = $lineDto->original_currency_id;

                // Fallback logic for backward compatibility
                if ($originalAmount === null) {
                    // Use the larger of debit or credit amount in the journal entry's currency
                    $originalAmount = $lineDto->debit->isPositive() ? $lineDto->debit : $lineDto->credit;
                    $originalCurrencyId = $journalEntry->currency_id;
                }

                if ($originalCurrencyId === null) {
                    // Default to journal entry's currency if not specified
                    $originalCurrencyId = $journalEntry->currency_id;
                }

                // Determine exchange rate (fallback to 1.0 if not provided)
                $exchangeRate = $lineDto->exchange_rate_at_transaction ?? 1.0;

                $line->fill([
                    'account_id' => $lineDto->account_id,
                    'partner_id' => $lineDto->partner_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                    'description' => $lineDto->description,
                    'debit' => $lineDto->debit,
                    'credit' => $lineDto->credit,
                    'original_currency_amount' => $originalAmount,
                    'original_currency_id' => $originalCurrencyId,
                    'exchange_rate_at_transaction' => $exchangeRate,
                ]);

                // Finally, save the fully prepared line.
                $line->save();
            }

            return $journalEntry;
        });
    }
}
