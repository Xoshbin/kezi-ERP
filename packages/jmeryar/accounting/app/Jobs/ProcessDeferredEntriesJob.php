<?php

namespace Jmeryar\Accounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Accounting\Services\DeferredItemService;

class ProcessDeferredEntriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DeferredItemService $deferredItemService): void
    {
        $deferredItemService->processDueEntries();
    }
}
