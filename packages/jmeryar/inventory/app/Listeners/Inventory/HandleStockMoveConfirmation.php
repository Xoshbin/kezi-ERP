<?php

namespace Jmeryar\Inventory\Listeners\Inventory;

use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Events\Inventory\StockMoveConfirmed;
use Jmeryar\Inventory\Jobs\Inventory\ProcessIncomingStockJob;
use Jmeryar\Inventory\Jobs\Inventory\ProcessOutgoingStockJob;

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
