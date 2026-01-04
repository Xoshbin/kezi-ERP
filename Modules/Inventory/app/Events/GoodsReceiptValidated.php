<?php

namespace Modules\Inventory\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\StockPicking;

/**
 * Event dispatched when a Goods Receipt (StockPicking of type Receipt) is validated.
 *
 * This event triggers:
 * - PurchaseOrderLine quantity_received updates
 * - Inventory valuation / cost layer creation
 * - PurchaseOrder status updates
 */
class GoodsReceiptValidated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<int, array{product_id: int, quantity: float, lot_id: int|null}>  $receivedLines
     */
    public function __construct(
        public StockPicking $stockPicking,
        public User $user,
        public array $receivedLines = [],
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
