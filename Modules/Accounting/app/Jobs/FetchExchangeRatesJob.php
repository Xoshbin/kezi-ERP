<?php

namespace Modules\Accounting\Jobs;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * FetchExchangeRatesJob
 *
 * Scheduled job to fetch exchange rates from external APIs.
 * Can be scheduled to run daily to keep exchange rates up to date.
 */
class FetchExchangeRatesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ?int $companyId = null,
        public ?Carbon $date = null,
        public string $source = 'frankfurter',
    ) {}

    public function handle(): void
    {
        Log::info('FetchExchangeRatesJob: Starting exchange rate fetch', [
            'company_id' => $this->companyId,
            'date' => $this->date?->toDateString() ?? 'today',
            'source' => $this->source,
        ]);

        $options = [
            '--source' => $this->source,
        ];

        if ($this->companyId) {
            $options['--company'] = $this->companyId;
        }

        if ($this->date) {
            $options['--date'] = $this->date->toDateString();
        }

        try {
            Artisan::call('accounting:fetch-exchange-rates', $options);
            $output = Artisan::output();

            Log::info('FetchExchangeRatesJob: Completed', [
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('FetchExchangeRatesJob: Failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Dispatch a job for all companies.
     */
    public static function dispatchForAllCompanies(string $source = 'frankfurter'): void
    {
        Company::all()->each(function (Company $company) use ($source) {
            static::dispatch($company->id, null, $source);
        });
    }
}

