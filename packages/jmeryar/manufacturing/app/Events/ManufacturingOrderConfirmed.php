<?php

namespace Jmeryar\Manufacturing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;

class ManufacturingOrderConfirmed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ManufacturingOrder $manufacturingOrder,
    ) {}
}
