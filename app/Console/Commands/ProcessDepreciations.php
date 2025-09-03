<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDepreciationJob;
use App\Models\DepreciationEntry;
use Illuminate\Console\Command;

class ProcessDepreciations extends Command
{
    protected $signature = 'app:process-depreciations';

    protected $description = 'Process due depreciation entries';

    public function handle()
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
