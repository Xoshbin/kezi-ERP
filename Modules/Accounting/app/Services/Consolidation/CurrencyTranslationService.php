<?php

namespace Modules\Accounting\Services\Consolidation;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use InvalidArgumentException;
use Modules\Accounting\Enums\Consolidation\CurrencyTranslationMethod;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Services\CurrencyConverterService;

class CurrencyTranslationService
{
    public function __construct(
        protected CurrencyConverterService $converter
    ) {}

    /**
     * Translate an amount to the target currency using the specified method.
     *
     * @param  Carbon  $date  Point in time (reporting date or transaction date)
     * @param  Company  $company  Context company (usually the parent/consolidator)
     * @param  array|null  $period  ['start' => Carbon, 'end' => Carbon] Required for Average Rate
     */
    public function translate(
        Money $amount,
        Currency $targetCurrency,
        Carbon $date,
        CurrencyTranslationMethod $method,
        Company $company,
        ?array $period = null
    ): Money {
        $sourceCurrencyCode = $amount->getCurrency()->getCurrencyCode();
        if ($sourceCurrencyCode === $targetCurrency->code) {
            return $amount;
        }

        switch ($method) {
            case CurrencyTranslationMethod::ClosingRate:
            case CurrencyTranslationMethod::HistoricalRate:
                // Closing Rate: Use rate at reporting date ($date)
                // Historical Rate: Use rate at transaction date ($date passed by caller)
                return $this->converter->convert($amount, $targetCurrency, $date, $company);

            case CurrencyTranslationMethod::AverageRate:
                if (! $period || ! isset($period['start'], $period['end'])) {
                    throw new InvalidArgumentException('Period start and end dates are required for Average Rate translation.');
                }

                $averageRate = $this->getAverageRate(
                    Currency::where('code', $sourceCurrencyCode)->firstOrFail(),
                    $targetCurrency,
                    $period['start'],
                    $period['end'],
                    $company
                );

                if ($averageRate === null) {
                    // Fallback?? Or standard converter logic?
                    // Standard converter throws exception.
                    throw new InvalidArgumentException("No average rate calculable for {$sourceCurrencyCode} to {$targetCurrency->code}");
                }

                return $this->converter->convertWithRate($amount, $averageRate, $targetCurrency->code, false); // false = convert Foreign -> Base (Multiply)
                // convertWithRate signature: (Money $amount, float $rate, string $toCurrencyCode, bool $isFromBaseCurrency = false)
                // getAverageRate returns "How many Base = 1 Foreign"?

                // Let's clarify getAverageRate return logic.

            default:
                throw new InvalidArgumentException("Unsupported translation method: {$method->value}");
        }
    }

    /**
     * Calculate average exchange rate over a period.
     * Rate = Base Currency / Foreign Currency ??
     * Usually rates are stored as 1 Foreign = X Base.
     */
    protected function getAverageRate(Currency $from, Currency $to, Carbon $start, Carbon $end, Company $company): ?float
    {
        // Assuming rates are stored as 1 unit of $from = X units of Company Base Currency.
        // If $to is base currency.

        $baseCurrency = $company->currency;

        // Complex case: Neither is base currency. simple: source -> base -> target.
        // We will assume simpler case: consolidating to Parent (Base) Currency.
        // So $to should be $baseCurrency.

        if ($to->id !== $baseCurrency->id) {
            // If we need cross-rate average, it's safer to average source->base and then convert base->target (if constant?)
            // For consolidation, we usually convert Subsidiary (Foreign) -> Parent (Base).
        }

        // Query rates for $from currency in $company context within range.
        $averageRate = CurrencyRate::query()
            ->where('currency_id', $from->id)
            ->where('company_id', $company->id)
            ->whereDate('effective_date', '>=', $start)
            ->whereDate('effective_date', '<=', $end)
            ->avg('rate');

        return $averageRate ? (float) $averageRate : null;
    }
}
