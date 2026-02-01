<?php

namespace Jmeryar\Accounting\Services;

use App\Models\Company;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\DataTransferObjects\Currency\ForeignCurrencyBalanceDTO;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Services\CurrencyConverterService;

/**
 * CurrencyRevaluationService
 *
 * Orchestrates the currency revaluation process for foreign currency balances.
 * This service identifies accounts with foreign currency exposure and calculates
 * unrealized gains/losses based on current exchange rates.
 */
class CurrencyRevaluationService
{
    public function __construct(
        protected CurrencyConverterService $currencyConverter,
    ) {}

    /**
     * Get accounts eligible for revaluation.
     * Typically AR (Receivable) and AP (Payable) accounts that have foreign currency transactions.
     *
     * @param  array<int>  $accountIds  Optional specific accounts to filter
     * @return Collection<int, Account>
     */
    public function getEligibleAccounts(Company $company, array $accountIds = []): Collection
    {
        $query = Account::query()
            ->where('company_id', $company->id)
            ->where('is_deprecated', false)
            ->whereIn('type', [
                AccountType::Receivable->value,
                AccountType::Payable->value,
                AccountType::BankAndCash->value,
            ]);

        if (! empty($accountIds)) {
            $query->whereIn('id', $accountIds);
        }

        return $query->get();
    }

    /**
     * Get foreign currency balances for an account as of a specific date.
     *
     * @param  array<int>  $currencyIds  Optional specific currencies to filter
     * @return Collection<int, ForeignCurrencyBalanceDTO>
     */
    public function getForeignCurrencyBalances(
        Account $account,
        Company $company,
        Carbon $asOfDate,
        array $currencyIds = [],
    ): Collection {
        $baseCurrencyId = $company->currency_id;
        $baseCurrencyCode = $company->currency->code;

        // Query journal entry lines with foreign currency amounts
        $query = DB::table('journal_entry_lines as jel')
            ->select([
                'jel.account_id',
                'jel.original_currency_id as currency_id',
                'jel.partner_id',
                DB::raw('SUM(jel.original_currency_amount) as total_foreign_amount'),
                DB::raw('SUM(jel.debit) as total_debit'),
                DB::raw('SUM(jel.credit) as total_credit'),
                DB::raw('SUM(jel.original_currency_amount * jel.exchange_rate_at_transaction) as weighted_sum'),
            ])
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('jel.account_id', $account->id)
            ->where('je.state', JournalEntryState::Posted->value)
            ->where('je.entry_date', '<=', $asOfDate)
            ->whereNotNull('jel.original_currency_id')
            ->where('jel.original_currency_id', '!=', $baseCurrencyId)
            ->groupBy('jel.account_id', 'jel.original_currency_id', 'jel.partner_id');

        if (! empty($currencyIds)) {
            $query->whereIn('jel.original_currency_id', $currencyIds);
        }

        $results = $query->get();

        return $results->map(function ($row) use ($baseCurrencyCode) {
            $foreignAmount = (int) $row->total_foreign_amount;
            $totalDebit = (int) $row->total_debit;
            $totalCredit = (int) $row->total_credit;
            $bookValue = $totalDebit - $totalCredit;

            // Calculate weighted average rate
            $weightedAvgRate = $foreignAmount > 0
                ? (float) $row->weighted_sum / $foreignAmount
                : 0.0;

            $currency = Currency::find($row->currency_id);
            $currencyCode = $currency?->code ?? 'USD';

            return new ForeignCurrencyBalanceDTO(
                account_id: (int) $row->account_id,
                currency_id: (int) $row->currency_id,
                partner_id: $row->partner_id ? (int) $row->partner_id : null,
                foreign_balance: Money::ofMinor($foreignAmount, $currencyCode),
                book_value: Money::ofMinor($bookValue, $baseCurrencyCode),
                weighted_avg_rate: $weightedAvgRate,
            );
        })->filter(fn (ForeignCurrencyBalanceDTO $dto) => ! $dto->foreign_balance->isZero());
    }

    /**
     * Calculate the unrealized gain/loss for a foreign currency balance.
     *
     * @return array{current_rate: float, revalued_amount: Money, adjustment: Money}
     */
    public function calculateUnrealizedGainLoss(
        ForeignCurrencyBalanceDTO $balance,
        Company $company,
        Carbon $revaluationDate,
    ): array {
        // Ensure the company's currency relationship is loaded
        if (! $company->relationLoaded('currency')) {
            $company->load('currency');
        }

        $currency = Currency::find($balance->currency_id);
        if (! $currency) {
            return [
                'current_rate' => 0.0,
                'revalued_amount' => Money::zero($company->currency->code),
                'adjustment' => Money::zero($company->currency->code),
            ];
        }

        $currentRate = $this->currencyConverter->getExchangeRate($currency, $revaluationDate, $company);

        if ($currentRate === null) {
            $currentRate = $this->currencyConverter->getLatestExchangeRate($currency, $company) ?? 0.0;
        }

        // Revalue the foreign balance at the current rate
        // getAmount() returns the amount in major units (e.g., 1000.00 for USD 1000.00)
        // So we can directly multiply by the exchange rate
        $foreignMajorUnits = $balance->foreign_balance->getAmount();

        // Multiply by the exchange rate to get base currency major units
        $baseMajorUnits = $foreignMajorUnits->toBigDecimal()->multipliedBy(BigDecimal::of((string) $currentRate));

        // Convert to base currency minor units
        // Use Brick\Money's currency definition as the source of truth for decimal places
        $baseCurrencyCode = $company->currency->code;
        $baseBrickCurrency = \Brick\Money\Currency::of($baseCurrencyCode);
        $baseCurrencyDecimalPlaces = $baseBrickCurrency->getDefaultFractionDigits();

        $baseMinorUnits = $baseMajorUnits
            ->multipliedBy(BigDecimal::of(10)->power($baseCurrencyDecimalPlaces))
            ->toScale(0, RoundingMode::HALF_UP);

        $revaluedMoney = Money::ofMinor(
            (int) $baseMinorUnits->toInt(),
            $company->currency->code
        );

        // Adjustment = Revalued Amount - Book Value
        $adjustment = $revaluedMoney->minus($balance->book_value);

        return [
            'current_rate' => $currentRate,
            'revalued_amount' => $revaluedMoney,
            'adjustment' => $adjustment,
        ];
    }
}
