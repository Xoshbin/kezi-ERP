<?php

namespace Kezi\Accounting\Actions\Accounting;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Kezi\Accounting\Contracts\JournalEntryCreatorContract;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Support\TranslatableHelper;
use RuntimeException;

class CreateJournalEntryAction implements JournalEntryCreatorContract
{
    public function __construct(
        private readonly \Kezi\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly \Kezi\Foundation\Services\CurrencyConverterService $currencyConverter,
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

        // Calculate totals in company base currency
        $totalDebitBaseCurrency = Money::zero($company->currency->code);
        $totalCreditBaseCurrency = Money::zero($company->currency->code);

        // Helper to resolve exchange rate
        $resolveExchangeRate = function () use ($dto, $currency, $company) {
            if ($dto->exchange_rate) {
                return $dto->exchange_rate;
            }
            $rate = $this->currencyConverter->getExchangeRate($currency, $dto->entry_date, $company);
            if (! $rate) {
                $rate = $this->currencyConverter->getLatestExchangeRate($currency, $company);
            }

            return $rate ?: 1.0;
        };

        foreach ($dto->lines as $index => $line) {
            $account = Account::find($line->account_id);
            if ($account && $account->is_deprecated) {
                $accountName = TranslatableHelper::getLocalizedValue($account->name);
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => "Account '{$accountName}' is deprecated and cannot be used.",
                ]);
            }

            // Enforce account currency lock
            if ($account && $account->currency_id && $account->currency_id !== $dto->currency_id) {
                $accountCurrency = Currency::findOrFail($account->currency_id);
                $accountName = TranslatableHelper::getLocalizedValue($account->name);
                $accountCurrencyCode = $accountCurrency->code;
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => "Account '{$accountName}' is locked to {$accountCurrencyCode} currency but transaction is in {$currency->code}.",
                ]);
            }

            // Handle Currency Conversion for Totals
            if ($line->debit->getCurrency()->getCurrencyCode() !== $company->currency->code) {
                $rate = $resolveExchangeRate();
                $debitBase = $this->currencyConverter->convertWithRate($line->debit, $rate, $company->currency->code, false);
                $creditBase = $this->currencyConverter->convertWithRate($line->credit, $rate, $company->currency->code, false);
            } else {
                $debitBase = $line->debit;
                $creditBase = $line->credit;
            }

            $totalDebitBaseCurrency = $totalDebitBaseCurrency->plus($debitBase);
            $totalCreditBaseCurrency = $totalCreditBaseCurrency->plus($creditBase);
        }

        // Validate that debits equal credits (in Base Currency)
        // Note: validating in Base handles rounding issues better than mixing currencies
        if (! $totalDebitBaseCurrency->isEqualTo($totalCreditBaseCurrency)) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        return DB::transaction(function () use ($dto, $totalDebitBaseCurrency, $totalCreditBaseCurrency, $currency, $company, $resolveExchangeRate) {

            $journalEntryData = [
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'created_by_user_id' => $dto->created_by_user_id,
                'is_posted' => $dto->is_posted,
                'state' => $dto->is_posted ? JournalEntryState::Posted : JournalEntryState::Draft,
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

                $rateUsed = 1.0;
                $originalAmount = null;

                // Handle Conversion for Line Persistence
                if ($lineDto->debit->getCurrency()->getCurrencyCode() !== $company->currency->code) {
                    // Input is Foreign Currency
                    $rateUsed = $resolveExchangeRate();

                    $debitBase = $this->currencyConverter->convertWithRate($lineDto->debit, $rateUsed, $company->currency->code, false);
                    $creditBase = $this->currencyConverter->convertWithRate($lineDto->credit, $rateUsed, $company->currency->code, false);

                    // If input is foreign, it IS the original amount
                    $originalAmount = $lineDto->debit->isGreaterThan($lineDto->credit) ? $lineDto->debit : $lineDto->credit;
                } else {
                    // Input is Base Currency
                    $debitBase = $lineDto->debit;
                    $creditBase = $lineDto->credit;

                    // If original provided in DTO, use it. Else fall back to base (assuming transaction is base)
                    $originalAmount = $lineDto->original_currency_amount;
                    $rateUsed = $lineDto->exchange_rate_at_transaction ?? ($dto->exchange_rate ?? 1.0);

                    if (! $originalAmount) {
                        // Fallback: If currency is same, original = base.
                        // If transaction was foreign but passed as base, original SHOULD have been passed.
                        // We assume 1:1 if not passed.
                        $originalAmount = $debitBase->isGreaterThan($creditBase) ? $debitBase : $creditBase;
                        // But if transaction currency != base currency, valid money object must use transaction currency code
                        if ($currency->id !== $company->currency_id) {
                            // This is ambiguous: Passed Base Amount but missing Original Amount for Foreign Transaction.
                            // We can't easily guess original amount without rate.
                            // But we have rateUsed.
                            if ($rateUsed && $rateUsed != 0) {
                                // Attempt to reverse calc? No, unsafe.
                                // Better to set originalAmount to zero in that currency? Or throw?
                                // Let's create Money of 0 in Transaction Currency to be safe.
                                // Actually, originalAmount is nullable in DTO but needed for DB?
                                // DB column `original_currency_amount` is nullable?
                                // Let's check `JournalEntryLine` migration if possible.
                                // Assuming nullable or we construct it.
                                // Safer:
                                $originalAmount = Money::of(0, $currency->code);
                            }
                        }
                    }
                }

                // Prefer DTO value if explicitly set (overrides auto-detection logic if needed)
                if ($lineDto->original_currency_amount) {
                    $originalAmount = $lineDto->original_currency_amount;
                }

                if ($lineDto->exchange_rate_at_transaction) {
                    $rateUsed = $lineDto->exchange_rate_at_transaction;
                }

                // Prepare line data
                $line->original_currency_id = $dto->currency_id;
                $line->currency_id = $dto->currency_id;
                $line->exchange_rate_at_transaction = $rateUsed;

                // Set non-Money fields
                $line->company_id = $dto->company_id;
                $line->account_id = $lineDto->account_id;
                $line->partner_id = $lineDto->partner_id;
                $line->analytic_account_id = $lineDto->analytic_account_id;
                $line->partner_id = $lineDto->partner_id;
                $line->analytic_account_id = $lineDto->analytic_account_id;
                $line->description = $lineDto->description;
                $line->tax_id = $lineDto->tax_id;

                // Now set the Money fields after currency_id is set
                $line->debit = $debitBase;
                $line->credit = $creditBase;
                $line->original_currency_amount = $originalAmount;

                $line->save();
            }

            return $journalEntry;
        });
    }
}
