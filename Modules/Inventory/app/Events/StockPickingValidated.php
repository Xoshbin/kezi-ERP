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
 * Event dispatched when a StockPicking (receipt, delivery, or internal transfer) is validated.
 *
 * This event triggers:
 * - Quality check creation for goods receipts and internal transfers
 * - Other downstream processing based on the picking type
 */
class StockPickingValidated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StockPicking $stockPicking,
        public ?User $user = null,
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
