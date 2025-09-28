<?php

namespace Modules\Accounting\Console\Commands;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RevalueForeignCurrencyBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:revalue-foreign-currency-balances
                            {--company= : Company ID to revalue (optional, revalues all if not specified)}
                            {--date= : Revaluation date (defaults to today)}
                            {--accounts= : Comma-separated account IDs to revalue (optional)}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform period-end revaluation of foreign currency balances';

    protected \Modules\Accounting\Services\ExchangeGainLossService $exchangeGainLossService;

    public function __construct(\Modules\Accounting\Services\ExchangeGainLossService $exchangeGainLossService)
    {
        parent::__construct();
        $this->exchangeGainLossService = $exchangeGainLossService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting foreign currency revaluation...');

        // Parse options
        $companyId = $this->option('company');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $accountIds = $this->option('accounts')
            ? array_map('intval', explode(',', $this->option('accounts')))
            : [];
        $dryRun = $this->option('dry-run');

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

        $totalJournalEntries = 0;

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (ID: {$company->id})");

            if (! $dryRun) {
                $journalEntries = $this->exchangeGainLossService->performPeriodEndRevaluation(
                    $company,
                    $date,
                    $accountIds
                );

                $count = $journalEntries->count();
                $totalJournalEntries += $count;

                if ($count > 0) {
                    $this->info("  Created {$count} revaluation journal entries");

                    // Display summary of created entries
                    foreach ($journalEntries as $entry) {
                        $this->line("    - Entry #{$entry->id}: {$entry->reference} - {$entry->description}");
                    }
                } else {
                    $this->info('  No revaluation entries needed');
                }
            } else {
                // In dry-run mode, we would show what would be done
                $this->info("  [DRY RUN] Would perform revaluation for company {$company->name}");
            }
        }

        if (! $dryRun) {
            $this->info("Revaluation completed. Total journal entries created: {$totalJournalEntries}");
        } else {
            $this->info('Dry run completed. Use without --dry-run to perform actual revaluation.');
        }

        return 0;
    }
}
