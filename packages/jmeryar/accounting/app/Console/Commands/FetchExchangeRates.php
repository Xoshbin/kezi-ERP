<?php

namespace Jmeryar\Accounting\Console\Commands;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\ExchangeRate;

class FetchExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:fetch-exchange-rates
                            {--company= : Company ID to fetch rates for (optional, fetches for all if not specified)}
                            {--date= : Date to fetch rates for (defaults to today)}
                            {--source=frankfurter : Exchange rate source (frankfurter, exchangerate-api)}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch exchange rates from external APIs and store them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Fetching exchange rates...');

        $companyId = $this->option('company');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $source = $this->option('source');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get companies to process
        $companies = $companyId
            ? Company::where('id', $companyId)->get()
            : Company::all();

        if ($companies->isEmpty()) {
            $this->error('No companies found to process');

            return 1;
        }

        $totalRatesCreated = 0;

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (ID: {$company->id})");

            $baseCurrency = $company->currency;
            if (! $baseCurrency) {
                $this->warn('  Company has no base currency configured, skipping');

                continue;
            }

            // Get all active currencies except the base currency
            $currencies = Currency::where('is_active', true)
                ->where('id', '!=', $baseCurrency->id)
                ->get();

            if ($currencies->isEmpty()) {
                $this->info('  No foreign currencies to fetch rates for');

                continue;
            }

            $rates = $this->fetchRates($baseCurrency->code, $currencies->pluck('code')->toArray(), $date, $source);

            if (empty($rates)) {
                $this->warn('  Could not fetch rates from API');

                continue;
            }

            foreach ($currencies as $currency) {
                $rate = $rates[$currency->code] ?? null;

                if ($rate === null) {
                    $this->warn("  No rate found for {$currency->code}");

                    continue;
                }

                if ($dryRun) {
                    $this->line("  [DRY RUN] Would create rate: 1 {$baseCurrency->code} = {$rate} {$currency->code}");

                    continue;
                }

                // Check if rate already exists for this date
                $existingRate = ExchangeRate::where('currency_id', $currency->id)
                    ->where('company_id', $company->id)
                    ->whereDate('effective_date', $date)
                    ->first();

                if ($existingRate) {
                    $this->line("  Rate for {$currency->code} on {$date->toDateString()} already exists, updating");
                    $existingRate->update(['rate' => $rate]);
                } else {
                    ExchangeRate::create([
                        'currency_id' => $currency->id,
                        'company_id' => $company->id,
                        'rate' => $rate,
                        'effective_date' => $date,
                    ]);
                    $totalRatesCreated++;
                    $this->line("  Created rate: 1 {$baseCurrency->code} = {$rate} {$currency->code}");
                }
            }
        }

        if (! $dryRun) {
            $this->info("Exchange rate fetch completed. Total rates created: {$totalRatesCreated}");
        } else {
            $this->info('Dry run completed. Use without --dry-run to save rates.');
        }

        return 0;
    }

    /**
     * Fetch exchange rates from the specified source.
     *
     * @param  array<string>  $targetCurrencies
     * @return array<string, float>
     */
    protected function fetchRates(string $baseCurrency, array $targetCurrencies, Carbon $date, string $source): array
    {
        return match ($source) {
            'frankfurter' => $this->fetchFromFrankfurter($baseCurrency, $targetCurrencies, $date),
            'exchangerate-api' => $this->fetchFromExchangeRateApi($baseCurrency, $targetCurrencies, $date),
            default => [],
        };
    }

    /**
     * Fetch rates from Frankfurter API (free, no API key required).
     *
     * @param  array<string>  $targetCurrencies
     * @return array<string, float>
     */
    protected function fetchFromFrankfurter(string $baseCurrency, array $targetCurrencies, Carbon $date): array
    {
        try {
            $url = "https://api.frankfurter.app/{$date->toDateString()}";
            $response = Http::get($url, [
                'from' => $baseCurrency,
                'to' => implode(',', $targetCurrencies),
            ]);

            if ($response->successful()) {
                return $response->json('rates') ?? [];
            }

            $this->error("Frankfurter API error: {$response->status()}");

            return [];
        } catch (\Exception $e) {
            $this->error("Frankfurter API exception: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Fetch rates from ExchangeRate-API (requires API key in config).
     *
     * @param  array<string>  $targetCurrencies
     * @return array<string, float>
     */
    protected function fetchFromExchangeRateApi(string $baseCurrency, array $targetCurrencies, Carbon $date): array
    {
        $apiKey = config('services.exchangerate_api.key');

        if (! $apiKey) {
            $this->error('ExchangeRate-API key not configured');

            return [];
        }

        try {
            $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}";
            $response = Http::get($url);

            if ($response->successful()) {
                $allRates = $response->json('conversion_rates') ?? [];

                return array_filter(
                    $allRates,
                    fn ($key) => in_array($key, $targetCurrencies),
                    ARRAY_FILTER_USE_KEY
                );
            }

            $this->error("ExchangeRate-API error: {$response->status()}");

            return [];
        } catch (\Exception $e) {
            $this->error("ExchangeRate-API exception: {$e->getMessage()}");

            return [];
        }
    }
}
