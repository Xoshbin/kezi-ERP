<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\Company;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Accounting\DataTransferObjects\Accounting\UpdateJournalEntryDTO;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Foundation\Models\Currency;

class UpdateJournalEntryAction
{
    public function __construct(
        protected \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
    ) {}

    public function execute(UpdateJournalEntryDTO $dto): JournalEntry
    {
        $journalEntry = $dto->journalEntry;

        // 1. Perform all necessary validation before touching the database.
        $company = Company::find($journalEntry->company_id);
        if (! $company) {
            throw new InvalidArgumentException('Company not found');
        }
        $this->lockDateService->enforce($company, Carbon::parse($journalEntry->entry_date));

        if ($journalEntry->is_posted) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('Cannot modify a posted journal entry.');
        }

        $currency = Currency::find($dto->currency_id);
        if (! $currency) {
            throw new InvalidArgumentException('Currency not found');
        }
        $totalDebit = Money::zero($currency->code);
        $totalCredit = Money::zero($currency->code);

        foreach ($dto->lines as $line) {
            // Handle different types of input for debit and credit
            $debitMoney = $this->convertToMoney($line->debit, $currency->code);
            $creditMoney = $this->convertToMoney($line->credit, $currency->code);

            $totalDebit = $totalDebit->plus($debitMoney);
            $totalCredit = $totalCredit->plus($creditMoney);
        }

        if (! $totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // 2. Perform the update within a database transaction.
        return DB::transaction(function () use ($dto, $journalEntry, $currency) {
            // Update the parent model's main fields
            $journalEntry->update([
                'journal_id' => $dto->journal_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'is_posted' => $dto->is_posted,
            ]);

            // Sync the lines: delete the old ones
            $journalEntry->lines()->delete();

            // Create the new lines from the DTO
            if (! empty($dto->lines)) {
                foreach ($dto->lines as $lineDto) {
                    $line = new JournalEntryLine;

                    // First, establish the relationship. This makes the parent's context (like currency)
                    // available to the line model *before* any attributes are set. This is the key
                    // to solving the MoneyCast issue without schema changes.
                    $line->journalEntry()->associate($journalEntry);

                    // Set currency-related fields first to ensure proper context for Money casts
                    $line->original_currency_id = $dto->currency_id;
                    $line->currency_id = $dto->currency_id;
                    $line->exchange_rate_at_transaction = 1.0; // Default for same currency

                    // Now, fill the attributes. The MoneyCast on 'debit' and 'credit' will be
                    // triggered here, but it can now successfully call getCurrencyIdAttribute()
                    // because the journalEntry relationship is established.
                    $line->fill([
                        'company_id' => $journalEntry->company_id,
                        'account_id' => $lineDto->account_id,
                        'partner_id' => $lineDto->partner_id,
                        'analytic_account_id' => $lineDto->analytic_account_id,
                        'description' => $lineDto->description,
                        'debit' => Money::of($lineDto->debit, $currency->code),
                        'credit' => Money::of($lineDto->credit, $currency->code),
                        'original_currency_amount' => Money::of(
                            max($lineDto->debit, $lineDto->credit),
                            $currency->code
                        ),
                    ]);

                    // Finally, save the fully prepared line.
                    $line->save();
                }
            }

            // Recalculate totals from the new lines and save
            $journalEntry->calculateTotalsFromLines();
            $journalEntry->save();

            return $journalEntry;
        });
    }

    /**
     * Convert various input types to Money object
     *
     * @param  mixed  $value
     */
    private function convertToMoney($value, string $currencyCode): Money
    {
        // If it's already a Money object, return it
        if ($value instanceof Money) {
            return $value;
        }

        // If it's null or empty, return zero
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return Money::zero($currencyCode);
        }

        // If it's a string that might be formatted (e.g., "IQD 15000000.000")
        if (is_string($value)) {
            // Remove currency code and spaces, keep only numbers and decimal point
            $cleanValue = preg_replace('/[^0-9.]/', '', $value);
            if ($cleanValue === '' || $cleanValue === '.') {
                return Money::zero($currencyCode);
            }
            $value = $cleanValue;
        }

        // Convert to Money with rounding if necessary
        try {
            return Money::of($value, $currencyCode);
        } catch (RoundingNecessaryException) {
            return Money::of($value, $currencyCode, null, RoundingMode::HALF_UP);
        }
    }
}
