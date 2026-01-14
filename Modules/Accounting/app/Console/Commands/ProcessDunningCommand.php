<?php

namespace Modules\Accounting\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Modules\Accounting\Jobs\ProcessDunningJob;

class ProcessDunningCommand extends Command
{
    protected $signature = 'accounting:process-dunning';

    protected $description = 'Trigger dunning process for all companies';

    public function handle(): void
    {
        $this->info('Starting dunning process...');

        Company::query()->each(function (Company $company) {
            $this->info("Dispatching dunning job for company: {$company->name} ({$company->id})");
            ProcessDunningJob::dispatch($company->id);
        });

        $this->info('Dunning process dispatched successfully.');
    }
}
