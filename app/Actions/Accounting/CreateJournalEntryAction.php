<?php

namespace App\Actions\Accounting;

use Exception;
use App\Models\JournalEntryLine;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\Account;
use App\Services\Accounting\LockDateService;
use App\Services\CurrencyConverterService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJournalEntryAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CurrencyConverterService $currencyConverter
    ) {
    }

    public function execute(CreateJournalEntryDTO $dto): JournalEntry
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->entry_date));

        $currency = Currency::find($dto->currency_id);
        if (!$currency) {
            throw new Exception("Currency with ID {$dto->currency_id} not found.");
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

        // Calculate multi-currency totals if needed
        $exchangeRate = null;
        $totalDebitCompanyCurrency = null;
        $totalCreditCompanyCurrency = null;

        if ($currency->id !== $company->currency_id) {
            // Get exchange rate for the entry date
            $exchangeRate = $this->currencyConverter->getExchangeRate($currency, $dto->entry_date, $company);

            if (!$exchangeRate) {
                throw new Exception("No exchange rate found for {$currency->code} on {$dto->entry_date}");
            }

            // Convert totals to company currency
            $totalDebitCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
                $totalDebit,
                $currency,
                $company->currency,
                $dto->entry_date,
                $company
            );

            $totalCreditCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
                $totalCredit,
                $currency,
                $company->currency,
                $dto->entry_date,
                $company
            );
        }

        return DB::transaction(function () use ($dto, $totalDebit, $totalCredit, $exchangeRate, $totalDebitCompanyCurrency, $totalCreditCompanyCurrency, $currency, $company) {
            $journalEntryData = [
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
            ];

            // Add multi-currency fields if applicable
            if ($exchangeRate !== null) {
                $journalEntryData['exchange_rate_at_entry'] = $exchangeRate;
                $journalEntryData['total_debit_company_currency'] = $totalDebitCompanyCurrency;
                $journalEntryData['total_credit_company_currency'] = $totalCreditCompanyCurrency;
            }

            $journalEntry = JournalEntry::create($journalEntryData);

            // This ensures the $journalEntry object is fully hydrated before we use it.
            $journalEntry = $journalEntry->fresh()->load('currency');

            foreach ($dto->lines as $lineDto) {
                $line = new JournalEntryLine();

                // First, establish the relationship. This makes the parent's context (like currency)
                // available to the line model *before* any attributes are set. This is the key
                // to solving the MoneyCast issue without schema changes.
                $line->journalEntry()->associate($journalEntry);

                // Prepare line data
                $lineData = [
                    'company_id' => $dto->company_id,
                    'account_id' => $lineDto->account_id,
                    'partner_id' => $lineDto->partner_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                    'description' => $lineDto->description,
                    'debit' => $lineDto->debit,
                    'credit' => $lineDto->credit,
                ];

                // Add multi-currency fields if applicable
                if ($exchangeRate !== null) {
                    // Convert line amounts to company currency
                    $debitCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
                        $lineDto->debit,
                        $currency,
                        $company->currency,
                        $dto->entry_date,
                        $company
                    );

                    $creditCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
                        $lineDto->credit,
                        $currency,
                        $company->currency,
                        $dto->entry_date,
                        $company
                    );

                    $lineData['debit_company_currency'] = $debitCompanyCurrency;
                    $lineData['credit_company_currency'] = $creditCompanyCurrency;
                    $lineData['exchange_rate_at_transaction_decimal'] = $exchangeRate;

                    // Store original currency amount (the larger of debit or credit)
                    $originalAmount = $lineDto->debit->isGreaterThan($lineDto->credit) ? $lineDto->debit : $lineDto->credit;
                    $lineData['original_currency_amount'] = $originalAmount;
                }

                // Fill the attributes and save
                $line->fill($lineData);
                $line->save();
            }

            return $journalEntry;
        });
    }
}
