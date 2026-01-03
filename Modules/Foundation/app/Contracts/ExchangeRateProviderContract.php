<?php

namespace Modules\Foundation\Contracts;

use Carbon\Carbon;

/**
 * Contract for exchange rate providers.
 *
 * Implementations of this interface can fetch exchange rates from various sources
 * such as external APIs, central banks, or manual entry systems.
 */
interface ExchangeRateProviderContract
{
    /**
     * Get the provider identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get the provider display name.
     */
    public function getName(): string;

    /**
     * Check if the provider is available/configured.
     */
    public function isAvailable(): bool;

    /**
     * Fetch exchange rates for the given currencies.
     *
     * @param  string  $baseCurrency  The base currency code (e.g., 'USD')
     * @param  array<string>  $targetCurrencies  Array of target currency codes
     * @param  Carbon|null  $date  The date for historical rates (null for latest)
     * @return array<string, float> Map of currency code to exchange rate
     */
    public function fetchRates(string $baseCurrency, array $targetCurrencies, ?Carbon $date = null): array;

    /**
     * Fetch a single exchange rate.
     *
     * @param  string  $baseCurrency  The base currency code
     * @param  string  $targetCurrency  The target currency code
     * @param  Carbon|null  $date  The date for historical rates (null for latest)
     */
    public function fetchRate(string $baseCurrency, string $targetCurrency, ?Carbon $date = null): ?float;

    /**
     * Get the list of supported currencies.
     *
     * @return array<string> Array of supported currency codes
     */
    public function getSupportedCurrencies(): array;
}

