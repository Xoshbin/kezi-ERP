<?php

namespace Modules\Accounting\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounting\Jobs\ProcessDepreciationJob;
use Modules\Accounting\Models\DepreciationEntry;

class ProcessDepreciations extends Command
{
    protected $signature = 'app:process-depreciations';

    protected $description = 'Process due depreciation entries';

    public function handle(): void
    {
        $this->info('Processing depreciations...');

        $entries = DepreciationEntry::where('status', 'draft')
            ->where('depreciation_date', '<=', now())
            ->get();

        foreach ($entries as $entry) {
            ProcessDepreciationJob::dispatch($entry);
        }

        $this->info('Done.');
    }
}
