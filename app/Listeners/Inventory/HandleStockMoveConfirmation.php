<?php

namespace App\Listeners\Inventory;

use App\Enums\Inventory\StockMoveType;
use App\Events\Inventory\StockMoveConfirmed;
use App\Jobs\Inventory\ProcessIncomingStockJob;
use App\Jobs\Inventory\ProcessOutgoingStockJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleStockMoveConfirmation
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StockMoveConfirmed $event): void
    {
        $stockMove = $event->stockMove;

        if ($stockMove->move_type === StockMoveType::INCOMING) {
            ProcessIncomingStockJob::dispatch($stockMove);
        } elseif ($stockMove->move_type === StockMoveType::OUTGOING) {
            ProcessOutgoingStockJob::dispatch($stockMove);
        }
    }
}
