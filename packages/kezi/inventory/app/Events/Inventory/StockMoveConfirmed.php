<?php

namespace Kezi\Inventory\Events\Inventory;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kezi\Inventory\Models\StockMove;

class StockMoveConfirmed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly StockMove $stockMove,
    ) {}
}
