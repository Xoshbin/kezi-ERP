<?php

namespace Modules\Foundation\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates
                            {--source=api : Source of rates (api, manual)}
                            {--validate : Validate existing rates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates for all active currencies';

    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $source = $this->option('source');
        $validate = $this->option('validate');

        if ($validate) {
            return $this->validateRates();
        }

        $this->info('Updating exchange rates...');

        $results = $this->exchangeRateService->updateAllRates($source ?? 'default');

        $successCount = 0;
        $failureCount = 0;

        foreach ($results as $currencyCode => $result) {
            if ($result['success']) {
                $this->info("✓ {$currencyCode}: {$result['rate']}");
                $successCount++;
            } else {
                $this->error("✗ {$currencyCode}: {$result['error']}");
                $failureCount++;
            }
        }

        $this->info("Update completed. Success: {$successCount}, Failures: {$failureCount}");

        return $failureCount > 0 ? 1 : 0;
    }

    /**
     * Validate existing exchange rates.
     */
    protected function validateRates(): int
    {
        $this->info('Validating exchange rates...');

        $missingRates = $this->exchangeRateService->validateRecentRates();

        if (empty($missingRates)) {
            $this->info('✓ All currencies have recent exchange rates');

            return 0;
        }

        $this->warn('The following currencies are missing recent rates:');

        foreach ($missingRates as $missing) {
            $currency = $missing['currency'];
            $daysOld = $missing['days_old'] ?? 'N/A';
            $lastDate = $missing['latest_rate_date'] ?? 'Never';

            $this->line("  - {$currency->code} ({$currency->name}): Last rate {$daysOld} days old ({$lastDate})");
        }

        return 1;
    }
}
