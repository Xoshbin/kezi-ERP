<?php

namespace Kezi\Foundation\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Kezi\Foundation\Contracts\ExchangeRateProviderContract;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Foundation\Services\ExchangeRateProviders\ExchangeRateApiProvider;
use Kezi\Foundation\Services\ExchangeRateProviders\FrankfurterProvider;

/**
 * ExchangeRateService
 *
 * Manages exchange rates including fetching from external APIs,
 * storing historical rates, and providing rate management functionality.
 * Supports multiple exchange rate providers with fallback capability.
 */
class ExchangeRateService
{
    /** @var array<string, ExchangeRateProviderContract> */
    protected array $providers = [];

    protected ?string $defaultProvider = null;

    public function __construct()
    {
        // Register default providers
        $this->registerProvider(new FrankfurterProvider);
        $this->registerProvider(new ExchangeRateApiProvider);

        // Set default provider (Frankfurter is free and doesn't require API key)
        $this->defaultProvider = 'frankfurter';
    }

    /**
     * Register an exchange rate provider.
     */
    public function registerProvider(ExchangeRateProviderContract $provider): self
    {
        $this->providers[$provider->getIdentifier()] = $provider;

        return $this;
    }

    /**
     * Get a registered provider by identifier.
     */
    public function getProvider(string $identifier): ?ExchangeRateProviderContract
    {
        return $this->providers[$identifier] ?? null;
    }

    /**
     * Get all registered providers.
     *
     * @return array<string, ExchangeRateProviderContract>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get available (configured) providers.
     *
     * @return array<string, ExchangeRateProviderContract>
     */
    public function getAvailableProviders(): array
    {
        return array_filter($this->providers, fn ($p) => $p->isAvailable());
    }

    /**
     * Set the default provider.
     */
    public function setDefaultProvider(string $identifier): self
    {
        if (! isset($this->providers[$identifier])) {
            throw new \InvalidArgumentException("Provider '{$identifier}' is not registered");
        }

        $this->defaultProvider = $identifier;

        return $this;
    }

    /**
     * Update exchange rates for all active currencies from external API.
     *
     * @param  string  $source  The source identifier (e.g., 'api', 'manual')
     * @param  string|null  $providerIdentifier  Specific provider to use (null for default)
     * @return array<string, mixed> Results of the update operation
     */
    public function updateAllRates(string $source = 'api', ?string $providerIdentifier = null): array
    {
        $results = [];
        $activeCurrencies = Currency::where('is_active', true)->get();

        foreach ($activeCurrencies as $currency) {
            try {
                $rate = $this->fetchRateFromProvider($currency->code, null, $providerIdentifier);

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
     * Fetch rate from a provider with fallback support.
     *
     * @param  string  $currencyCode  The target currency code
     * @param  Carbon|null  $date  The date for historical rates
     * @param  string|null  $providerIdentifier  Specific provider to use
     */
    public function fetchRateFromProvider(string $currencyCode, ?Carbon $date = null, ?string $providerIdentifier = null): ?float
    {
        $baseCurrency = config('app.base_currency', 'USD');

        // Try specific provider first
        if ($providerIdentifier) {
            $provider = $this->getProvider($providerIdentifier);
            if ($provider && $provider->isAvailable()) {
                return $provider->fetchRate($baseCurrency, $currencyCode, $date);
            }
        }

        // Try default provider
        if ($this->defaultProvider) {
            $provider = $this->getProvider($this->defaultProvider);
            if ($provider && $provider->isAvailable()) {
                $rate = $provider->fetchRate($baseCurrency, $currencyCode, $date);
                if ($rate !== null) {
                    return $rate;
                }
            }
        }

        // Fallback to any available provider
        foreach ($this->getAvailableProviders() as $provider) {
            $rate = $provider->fetchRate($baseCurrency, $currencyCode, $date);
            if ($rate !== null) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Store a new exchange rate for a currency.
     */
    public function storeRate(Currency $currency, float $rate, Carbon $effectiveDate, string $source = 'manual', ?int $companyId = null): CurrencyRate
    {
        // Find existing rate for this currency, company, and date
        $existingRate = CurrencyRate::where('currency_id', $currency->id)
            ->where('company_id', $companyId)
            ->whereDate('effective_date', $effectiveDate)
            ->first();

        if ($existingRate) {
            $existingRate->update([
                'rate' => $rate,
                'source' => $source,
            ]);

            return $existingRate->fresh();
        }

        return CurrencyRate::create([
            'currency_id' => $currency->id,
            'company_id' => $companyId,
            'effective_date' => $effectiveDate,
            'rate' => $rate,
            'source' => $source,
        ]);
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
     * Returns the most recent rate on or before the given date.
     */
    public function getRateForDate(Currency $currency, Carbon $date): ?CurrencyRate
    {
        /** @var CurrencyRate|null $rate */
        $rate = $currency->rates()
            ->whereDate('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate;
    }

    /**
     * Get historical rates for a currency within a date range.
     *
     * @return Collection<int, CurrencyRate>
     */
    public function getHistoricalRates(Currency $currency, Carbon $startDate, Carbon $endDate)
    {
        /** @var Collection<int, \Kezi\Foundation\Models\CurrencyRate> */
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

    /**
     * Detect significant rate changes that may require review.
     *
     * @param  float  $thresholdPercent  Percentage change threshold (default 5%)
     * @return list<array<string, mixed>> Currencies with significant rate changes
     */
    public function detectSignificantRateChanges(float $thresholdPercent = 5.0): array
    {
        $significantChanges = [];
        $activeCurrencies = Currency::where('is_active', true)->get();

        foreach ($activeCurrencies as $currency) {
            $rates = $currency->rates()
                ->orderBy('effective_date', 'desc')
                ->limit(2)
                ->get();

            if ($rates->count() < 2) {
                continue;
            }

            $currentRate = $rates->first();
            $previousRate = $rates->last();

            if ($previousRate->rate == 0) {
                continue;
            }

            $changePercent = (($currentRate->rate - $previousRate->rate) / $previousRate->rate) * 100;

            if (abs($changePercent) >= $thresholdPercent) {
                $significantChanges[] = [
                    'currency' => $currency,
                    'previous_rate' => $previousRate->rate,
                    'current_rate' => $currentRate->rate,
                    'change_percent' => round($changePercent, 2),
                    'previous_date' => $previousRate->effective_date,
                    'current_date' => $currentRate->effective_date,
                ];
            }
        }

        return $significantChanges;
    }

    /**
     * Calculate the rate volatility for a currency over a period.
     *
     * @param  int  $days  Number of days to analyze
     * @return array{min: float, max: float, avg: float, volatility: float}|null
     */
    public function calculateRateVolatility(Currency $currency, int $days = 30): ?array
    {
        $startDate = Carbon::now()->subDays($days);
        $rates = $currency->rates()
            ->where('effective_date', '>=', $startDate->toDateString())
            ->orderBy('effective_date', 'asc')
            ->pluck('rate')
            ->toArray();

        if (count($rates) < 2) {
            return null;
        }

        $min = min($rates);
        $max = max($rates);
        $avg = array_sum($rates) / count($rates);

        // Calculate standard deviation as volatility measure
        $squaredDiffs = array_map(fn ($rate) => pow($rate - $avg, 2), $rates);
        $variance = array_sum($squaredDiffs) / count($rates);
        $volatility = sqrt($variance);

        return [
            'min' => round($min, 6),
            'max' => round($max, 6),
            'avg' => round($avg, 6),
            'volatility' => round($volatility, 6),
        ];
    }

    /**
     * Store rate with change detection and optional notification.
     *
     * @param  float  $significantChangeThreshold  Percentage threshold for significant changes
     * @return array{rate: CurrencyRate, is_significant_change: bool, change_percent: float|null}
     */
    public function storeRateWithChangeDetection(
        Currency $currency,
        float $rate,
        Carbon $effectiveDate,
        string $source = 'manual',
        float $significantChangeThreshold = 5.0,
    ): array {
        $previousRate = $this->getLatestRate($currency);
        $changePercent = null;
        $isSignificantChange = false;

        if ($previousRate && $previousRate->rate > 0) {
            $changePercent = (($rate - $previousRate->rate) / $previousRate->rate) * 100;
            $isSignificantChange = abs($changePercent) >= $significantChangeThreshold;
        }

        $storedRate = $this->storeRate($currency, $rate, $effectiveDate, $source);

        return [
            'rate' => $storedRate,
            'is_significant_change' => $isSignificantChange,
            'change_percent' => $changePercent !== null ? round($changePercent, 2) : null,
        ];
    }
}
