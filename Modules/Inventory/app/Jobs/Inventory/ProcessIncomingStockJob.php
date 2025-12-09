<?php

namespace Modules\Inventory\Jobs\Inventory;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Actions\Inventory\ProcessIncomingStockAction;
use Modules\Inventory\Models\StockMove;

class ProcessIncomingStockJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly StockMove $stockMove,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProcessIncomingStockAction $action): void
    {
        $action->execute($this->stockMove);
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->stockMove->id;
    }
}
