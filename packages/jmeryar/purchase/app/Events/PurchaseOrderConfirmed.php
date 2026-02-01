<?php

namespace Jmeryar\Purchase\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Purchase\Models\PurchaseOrder;

class PurchaseOrderConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PurchaseOrder $purchaseOrder
    ) {}
}
