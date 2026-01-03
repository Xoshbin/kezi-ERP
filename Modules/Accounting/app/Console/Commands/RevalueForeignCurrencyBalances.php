<?php

namespace Modules\Accounting\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Accounting\Actions\Currency\PerformCurrencyRevaluationAction;
use Modules\Accounting\DataTransferObjects\Currency\PerformRevaluationDTO;
use Modules\Accounting\Services\CurrencyRevaluationService;

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
                            {--currencies= : Comma-separated currency IDs to revalue (optional)}
                            {--auto-post : Automatically post the revaluation entries}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform period-end revaluation of foreign currency balances';

    public function __construct(
        protected PerformCurrencyRevaluationAction $revaluationAction,
        protected CurrencyRevaluationService $revaluationService,
    ) {
        parent::__construct();
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
        $currencyIds = $this->option('currencies')
            ? array_map('intval', explode(',', $this->option('currencies')))
            : [];
        $autoPost = (bool) $this->option('auto-post');
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

        // Get a system user for the revaluation
        $systemUser = User::first();
        if (! $systemUser) {
            $this->error('No users found in the system');

            return 1;
        }

        $totalRevaluations = 0;

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (ID: {$company->id})");

            if ($dryRun) {
                // Show preview of what would be done
                $this->showDryRunPreview($company, $date, $accountIds, $currencyIds);

                continue;
            }

            try {
                $dto = new PerformRevaluationDTO(
                    company_id: $company->id,
                    created_by_user_id: $systemUser->id,
                    revaluation_date: $date,
                    description: "Period-end currency revaluation as of {$date->toDateString()}",
                    account_ids: $accountIds,
                    currency_ids: $currencyIds,
                    auto_post: $autoPost,
                );

                $revaluation = $this->revaluationAction->execute($dto);
                $lineCount = $revaluation->lines->count();

                if ($lineCount > 0) {
                    $this->info("  Created revaluation #{$revaluation->reference} with {$lineCount} lines");
                    $this->line("    Net adjustment: {$revaluation->net_adjustment}");
                    $this->line("    Status: {$revaluation->status->value}");
                    $totalRevaluations++;
                } else {
                    $this->info('  No foreign currency balances requiring revaluation');
                }
            } catch (\Exception $e) {
                $this->error("  Error processing company: {$e->getMessage()}");
            }
        }

        $this->info("Revaluation completed. Total revaluations created: {$totalRevaluations}");

        return 0;
    }

    /**
     * Show a preview of what would be done in dry-run mode.
     *
     * @param  array<int>  $accountIds
     * @param  array<int>  $currencyIds
     */
    protected function showDryRunPreview(Company $company, Carbon $date, array $accountIds, array $currencyIds): void
    {
        $accounts = $this->revaluationService->getEligibleAccounts($company, $accountIds);

        foreach ($accounts as $account) {
            $balances = $this->revaluationService->getForeignCurrencyBalances(
                $account,
                $company,
                $date,
                $currencyIds,
            );

            foreach ($balances as $balance) {
                $result = $this->revaluationService->calculateUnrealizedGainLoss($balance, $company, $date);

                if (! $result['adjustment']->isZero()) {
                    $this->line("  [DRY RUN] Account {$account->code}: Would adjust {$result['adjustment']}");
                }
            }
        }
    }
}
