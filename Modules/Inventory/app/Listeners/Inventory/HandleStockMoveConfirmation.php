<?php

namespace Modules\Inventory\Listeners\Inventory;


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
