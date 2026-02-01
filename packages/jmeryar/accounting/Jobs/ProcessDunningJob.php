<?php

namespace Jmeryar\Accounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Accounting\Actions\Dunning\ProcessDunningRunAction;

class ProcessDunningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $companyId) {}

    public function handle(ProcessDunningRunAction $action): void
    {
        $action->execute($this->companyId);
    }
}
