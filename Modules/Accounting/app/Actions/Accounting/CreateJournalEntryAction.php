<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Foundation\Models\Currency;
use RuntimeException;

class CreateJournalEntryAction
{
    public function __construct(
        private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly \Modules\Foundation\Services\CurrencyConverterService $currencyConverter,
    ) {}

    public function execute(CreateJournalEntryDTO $dto): JournalEntry
    {

        if (empty($dto->entry_date)) {
            throw ValidationException::withMessages([
                'entry_date' => 'The entry date is required.',
            ]);
        }

        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->entry_date));

        if (count($dto->lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'The journal entry must have at least 2 lines.',
            ]);
        }

        $currency = Currency::find($dto->currency_id);
        if (! $currency) {
            throw new Exception("Currency with ID {$dto->currency_id} not found.");
        }

        // Validate account currency locks and calculate totals in company base currency
        $totalDebitBaseCurrency = Money::zero($company->currency->code);
        $totalCreditBaseCurrency = Money::zero($company->currency->code);

        foreach ($dto->lines as $index => $line) {
            $account = Account::find($line->account_id);
            if ($account && $account->is_deprecated) {
                $accountName = is_array($account->name) ? ($account->name['en'] ?? (empty($account->name) ? '' : (string) array_values($account->name)[0])) : (string) $account->name;
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => "Account '{$accountName}' is deprecated and cannot be used.",
                ]);
            }

            // Enforce account currency lock
            if ($account && $account->currency_id && $account->currency_id !== $dto->currency_id) {
                $accountCurrency = Currency::findOrFail($account->currency_id);
                $accountName = is_array($account->name) ? ($account->name['en'] ?? (empty($account->name) ? '' : (string) array_values($account->name)[0])) : (string) $account->name;
                $accountCurrencyCode = $accountCurrency->code;
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => "Account '{$accountName}' is locked to {$accountCurrencyCode} currency but transaction is in {$currency->code}.",
                ]);
            }

            // Convert line amounts to company base currency for totals calculation
            if ($currency->id !== $company->currency_id) {
                // Use provided exchange rate if available, otherwise get from currency converter
                $exchangeRate = $dto->exchange_rate;

                if (! $exchangeRate) {
                    // Get exchange rate with fallback logic
                    $exchangeRate = $this->currencyConverter->getExchangeRate($currency, $dto->entry_date, $company);

                    // If no exchange rate found for the specific date, try latest available rate
                    if (! $exchangeRate) {
                        $exchangeRate = $this->currencyConverter->getLatestExchangeRate($currency, $company);
                    }

                    // If still no rate found, use rate 1.0 as fallback
                    if (! $exchangeRate) {
                        $exchangeRate = 1.0;
                    }
                }

                $debitBaseCurrency = $this->currencyConverter->convertWithRate(
                    $line->debit,
                    $exchangeRate,
                    $company->currency->code,
                    false
                );
                $creditBaseCurrency = $this->currencyConverter->convertWithRate(
                    $line->credit,
                    $exchangeRate,
                    $company->currency->code,
                    false
                );
            } else {
                // Same currency, no conversion needed
                $debitBaseCurrency = $line->debit;
                $creditBaseCurrency = $line->credit;
            }

            $totalDebitBaseCurrency = $totalDebitBaseCurrency->plus($debitBaseCurrency);
            $totalCreditBaseCurrency = $totalCreditBaseCurrency->plus($creditBaseCurrency);
        }

        // Validate that debits equal credits (in original currency for consistency)
        $totalDebitOriginal = Money::zero($currency->code);
        $totalCreditOriginal = Money::zero($currency->code);
        foreach ($dto->lines as $line) {
            $totalDebitOriginal = $totalDebitOriginal->plus($line->debit);
            $totalCreditOriginal = $totalCreditOriginal->plus($line->credit);
        }

        if (! $totalDebitOriginal->isEqualTo($totalCreditOriginal)) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        return DB::transaction(function () use ($dto, $totalDebitBaseCurrency, $totalCreditBaseCurrency, $currency, $company) {

            $journalEntryData = [
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'created_by_user_id' => $dto->created_by_user_id,
                'is_posted' => $dto->is_posted,
                'total_debit' => $totalDebitBaseCurrency,
                'total_credit' => $totalCreditBaseCurrency,
                'source_type' => $dto->source_type,
                'source_id' => $dto->source_id,
            ];

            $journalEntry = JournalEntry::create($journalEntryData);

            // This ensures the $journalEntry object is fully hydrated before we use it.
            $journalEntry = $journalEntry->fresh();
            if (! $journalEntry) {
                throw new RuntimeException('Failed to refresh journal entry after creation');
            }
            $journalEntry->load('currency');

            foreach ($dto->lines as $lineDto) {
                $line = new JournalEntryLine;

                // First, establish the relationship. This makes the parent's context (like currency)
                // available to the line model *before* any attributes are set. This is the key
                // to solving the MoneyCast issue without schema changes.
                $line->journalEntry()->associate($journalEntry);

                // Convert line amounts to company base currency
                if ($currency->id !== $company->currency_id) {
                    // Use the same exchange rate logic as the first loop
                    $lineExchangeRate = $dto->exchange_rate;

                    if (! $lineExchangeRate) {
                        // Get exchange rate with fallback logic
                        $lineExchangeRate = $this->currencyConverter->getExchangeRate($currency, $dto->entry_date, $company);

                        // If no exchange rate found for the specific date, try latest available rate
                        if (! $lineExchangeRate) {
                            $lineExchangeRate = $this->currencyConverter->getLatestExchangeRate($currency, $company);
                        }

                        // If still no rate found, use rate 1.0 as fallback
                        if (! $lineExchangeRate) {
                            $lineExchangeRate = 1.0;
                        }
                    }

                    $debitBaseCurrency = $this->currencyConverter->convertWithRate(
                        $lineDto->debit,
                        $lineExchangeRate,
                        $company->currency->code,
                        false
                    );
                    $creditBaseCurrency = $this->currencyConverter->convertWithRate(
                        $lineDto->credit,
                        $lineExchangeRate,
                        $company->currency->code,
                        false
                    );
                } else {
                    // Same currency, no conversion needed
                    $debitBaseCurrency = $lineDto->debit;
                    $creditBaseCurrency = $lineDto->credit;
                }

                // Determine original currency amount and exchange rate
                $originalCurrencyAmount = $lineDto->original_currency_amount;
                $exchangeRateAtTransaction = $lineDto->exchange_rate_at_transaction;

                // If not provided in DTO, calculate defaults
                if ($originalCurrencyAmount === null) {
                    // Use the larger of debit or credit as the original amount
                    $originalCurrencyAmount = $lineDto->debit->isGreaterThan($lineDto->credit) ? $lineDto->debit : $lineDto->credit;
                }

                if ($exchangeRateAtTransaction === null) {
                    // Get exchange rate if converting between currencies
                    if ($currency->id !== $company->currency_id) {
                        // Use provided exchange rate if available, otherwise get from currency converter
                        $exchangeRateAtTransaction = $dto->exchange_rate ?? $this->currencyConverter->getExchangeRate($currency, $dto->entry_date, $company);
                    } else {
                        $exchangeRateAtTransaction = 1.0;
                    }
                }

                // Ensure we have a valid float value
                $exchangeRateAtTransaction = (float) $exchangeRateAtTransaction;

                // Prepare line data - store amounts as Money objects, let casts handle conversion
                // Set the currency-related fields first to avoid cast issues
                $line->original_currency_id = $dto->currency_id;
                $line->currency_id = $dto->currency_id;
                $line->exchange_rate_at_transaction = $exchangeRateAtTransaction;

                // Set non-Money fields
                $line->company_id = $dto->company_id;
                $line->account_id = $lineDto->account_id;
                $line->partner_id = $lineDto->partner_id;
                $line->analytic_account_id = $lineDto->analytic_account_id;
                $line->description = $lineDto->description;

                // Now set the Money fields after currency_id is set
                $line->debit = $debitBaseCurrency;
                $line->credit = $creditBaseCurrency;
                $line->original_currency_amount = $originalCurrencyAmount;

                $line->save();
            }

            return $journalEntry;
        });
    }
}
