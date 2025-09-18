<?php

namespace App\Listeners\Inventory;

use App\Enums\Inventory\StockMoveType;
use App\Events\Inventory\StockMoveConfirmed;
use App\Jobs\Inventory\ProcessIncomingStockJob;
use App\Jobs\Inventory\ProcessOutgoingStockJob;

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

        if ($stockMove->move_type === StockMoveType::Incoming) {
            // Use synchronous dispatch to ensure deterministic processing in tests
            ProcessIncomingStockJob::dispatchSync($stockMove);
        } elseif ($stockMove->move_type === StockMoveType::Outgoing) {
            ProcessOutgoingStockJob::dispatchSync($stockMove);
        }
    }
}
