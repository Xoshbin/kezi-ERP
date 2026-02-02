<?php

namespace Kezi\Foundation\Services\ExchangeRateProviders;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kezi\Foundation\Contracts\ExchangeRateProviderContract;

/**
 * ExchangeRate-API Provider
 *
 * Commercial API with free tier. Requires API key.
 * Supports more currencies than ECB-based providers.
 *
 * @see https://www.exchangerate-api.com/
 */
class ExchangeRateApiProvider implements ExchangeRateProviderContract
{
    protected const BASE_URL = 'https://v6.exchangerate-api.com/v6';

    public function getIdentifier(): string
    {
        return 'exchangerate-api';
    }

    public function getName(): string
    {
        return 'ExchangeRate-API';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('services.exchangerate_api.key'));
    }

    public function fetchRates(string $baseCurrency, array $targetCurrencies, ?Carbon $date = null): array
    {
        $apiKey = config('services.exchangerate_api.key');

        if (! $apiKey) {
            Log::warning('ExchangeRate-API key not configured');

            return [];
        }

        try {
            // Note: Free tier doesn't support historical rates
            $url = self::BASE_URL."/{$apiKey}/latest/{$baseCurrency}";
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $allRates = $response->json('conversion_rates') ?? [];

                return array_filter(
                    $allRates,
                    fn ($key) => in_array($key, $targetCurrencies),
                    ARRAY_FILTER_USE_KEY
                );
            }

            Log::warning("ExchangeRate-API error: {$response->status()}", [
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("ExchangeRate-API exception: {$e->getMessage()}");

            return [];
        }
    }

    public function fetchRate(string $baseCurrency, string $targetCurrency, ?Carbon $date = null): ?float
    {
        $rates = $this->fetchRates($baseCurrency, [$targetCurrency], $date);

        return $rates[$targetCurrency] ?? null;
    }

    public function getSupportedCurrencies(): array
    {
        $apiKey = config('services.exchangerate_api.key');

        if (! $apiKey) {
            return [];
        }

        try {
            $url = self::BASE_URL."/{$apiKey}/codes";
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $codes = $response->json('supported_codes') ?? [];

                return array_column($codes, 0);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("ExchangeRate-API codes fetch failed: {$e->getMessage()}");

            return [];
        }
    }
}
