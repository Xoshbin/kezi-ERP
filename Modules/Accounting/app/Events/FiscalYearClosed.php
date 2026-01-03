<?php

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Models\FiscalYear;

class FiscalYearClosed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public FiscalYear $fiscalYear
    ) {}
}
