<?php

namespace Jmeryar\Foundation\Services\ExchangeRateProviders;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jmeryar\Foundation\Contracts\ExchangeRateProviderContract;

/**
 * Frankfurter API Exchange Rate Provider
 *
 * Free, open-source API for exchange rates published by the European Central Bank.
 * No API key required. Supports historical rates.
 *
 * @see https://www.frankfurter.app/
 */
class FrankfurterProvider implements ExchangeRateProviderContract
{
    protected const BASE_URL = 'https://api.frankfurter.app';

    public function getIdentifier(): string
    {
        return 'frankfurter';
    }

    public function getName(): string
    {
        return 'Frankfurter (ECB)';
    }

    public function isAvailable(): bool
    {
        // Frankfurter is always available (no API key required)
        return true;
    }

    public function fetchRates(string $baseCurrency, array $targetCurrencies, ?Carbon $date = null): array
    {
        try {
            $endpoint = $date ? "/{$date->toDateString()}" : '/latest';
            $url = self::BASE_URL.$endpoint;

            $response = Http::timeout(10)->get($url, [
                'from' => $baseCurrency,
                'to' => implode(',', $targetCurrencies),
            ]);

            if ($response->successful()) {
                return $response->json('rates') ?? [];
            }

            Log::warning("Frankfurter API error: {$response->status()}", [
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Frankfurter API exception: {$e->getMessage()}");

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
        try {
            $response = Http::timeout(10)->get(self::BASE_URL.'/currencies');

            if ($response->successful()) {
                return array_keys($response->json() ?? []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Frankfurter currencies fetch failed: {$e->getMessage()}");

            return [];
        }
    }
}
