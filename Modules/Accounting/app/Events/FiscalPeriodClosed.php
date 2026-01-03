<?php

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodClosed
{
    use Dispatchable;

    public function __construct(
        public FiscalPeriod $fiscalPeriod,
    ) {}
}
