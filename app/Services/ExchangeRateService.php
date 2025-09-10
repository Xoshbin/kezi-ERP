<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ExchangeRateService
 *
 * Manages exchange rates including fetching from external APIs,
 * storing historical rates, and providing rate management functionality.
 */
class ExchangeRateService
{
    /**
     * Update exchange rates for all active currencies from external API.
     *
     * @param  string  $source  The source identifier (e.g., 'api', 'manual')
     * @return array<string, mixed> Results of the update operation
     */
    public function updateAllRates(string $source = 'api'): array
    {
        $results = [];
        $activeCurrencies = Currency::where('is_active', true)->get();

        foreach ($activeCurrencies as $currency) {
            try {
                $rate = $this->fetchRateFromAPI($currency->code);

                if ($rate !== null) {
                    $this->storeRate($currency, $rate, Carbon::today(), $source);
                    $results[$currency->code] = ['success' => true, 'rate' => $rate];
                } else {
                    $results[$currency->code] = ['success' => false, 'error' => 'No rate returned from API'];
                }
            } catch (Exception $e) {
                Log::error("Failed to update rate for {$currency->code}: ".$e->getMessage());
                $results[$currency->code] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Store a new exchange rate for a currency.
     */
    public function storeRate(Currency $currency, float $rate, Carbon $effectiveDate, string $source = 'manual'): CurrencyRate
    {
        // Check if a rate already exists for this currency and date
        $existingRate = CurrencyRate::where('currency_id', $currency->id)
            ->where('effective_date', $effectiveDate->toDateString())
            ->first();

        if ($existingRate) {
            // Update existing rate
            $existingRate->update([
                'rate' => $rate,
                'source' => $source,
            ]);

            return $existingRate;
        }

        // Create new rate
        return CurrencyRate::create([
            'currency_id' => $currency->id,
            'rate' => $rate,
            'effective_date' => $effectiveDate,
            'source' => $source,
        ]);
    }

    /**
     * Fetch exchange rate from external API.
     * This is a basic implementation - you can extend it to support multiple providers.
     */
    protected function fetchRateFromAPI(string $currencyCode): ?float
    {
        try {
            // Using exchangerate-api.com as an example (free tier available)
            // You should configure the base currency and API key in your .env file
            $baseCurrency = config('app.base_currency', 'USD');
            $apiKey = config('services.exchange_rate_api.key');

            if (! $apiKey) {
                Log::warning('Exchange rate API key not configured');

                return null;
            }

            $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$baseCurrency}/{$currencyCode}");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['conversion_rate'])) {
                    return (float) $data['conversion_rate'];
                }
            }

            Log::warning("Failed to fetch rate for {$currencyCode}: ".$response->body());

            return null;

        } catch (Exception $e) {
            Log::error("API request failed for {$currencyCode}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get the latest rate for a currency.
     */
    public function getLatestRate(Currency $currency): ?CurrencyRate
    {
        /** @var CurrencyRate|null $rate */
        $rate = $currency->rates()
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate;
    }

    /**
     * Get the rate for a currency on a specific date.
     */
    public function getRateForDate(Currency $currency, Carbon $date): ?CurrencyRate
    {
        /** @var CurrencyRate|null $rate */
        $rate = $currency->rates()
            ->where('effective_date', '<=', $date->toDateString())
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate;
    }

    /**
     * Get historical rates for a currency within a date range.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\CurrencyRate>
     */
    public function getHistoricalRates(Currency $currency, Carbon $startDate, Carbon $endDate)
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\CurrencyRate> */
        return $currency->rates()
            ->whereBetween('effective_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('effective_date', 'asc')
            ->get();
    }

    /**
     * Check if a rate exists for a currency on a specific date.
     */
    public function hasRateForDate(Currency $currency, Carbon $date): bool
    {
        return $currency->rates()
            ->where('effective_date', $date->toDateString())
            ->exists();
    }

    /**
     * Delete old rates beyond a certain retention period.
     * This helps manage database size while keeping essential historical data.
     *
     * @param  int  $retentionDays  Number of days to retain
     * @return int Number of deleted records
     */
    public function cleanupOldRates(int $retentionDays = 2555): int // ~7 years default
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        return CurrencyRate::where('effective_date', '<', $cutoffDate->toDateString())
            ->delete();
    }

    /**
     * Validate that all active currencies have recent rates.
     *
     * @param  int  $maxDaysOld  Maximum age of rates in days
     * @return list<array<string, mixed>> Currencies missing recent rates
     */
    public function validateRecentRates(int $maxDaysOld = 7): array
    {
        $cutoffDate = Carbon::now()->subDays($maxDaysOld);
        $missingRates = [];

        $activeCurrencies = Currency::where('is_active', true)->get();

        foreach ($activeCurrencies as $currency) {
            $latestRate = $this->getLatestRate($currency);

            if (! $latestRate || $latestRate->effective_date->lt($cutoffDate)) {
                $missingRates[] = [
                    'currency' => $currency,
                    'latest_rate_date' => $latestRate?->effective_date,
                    'days_old' => $latestRate ? $latestRate->effective_date->diffInDays(Carbon::now()) : null,
                ];
            }
        }

        return $missingRates;
    }
}
