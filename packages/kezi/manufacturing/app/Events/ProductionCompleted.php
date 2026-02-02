<?php

namespace Kezi\Manufacturing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kezi\Manufacturing\Models\ManufacturingOrder;

class ProductionCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ManufacturingOrder $manufacturingOrder,
    ) {}
}
