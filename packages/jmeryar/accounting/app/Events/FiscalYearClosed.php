<?php

namespace Jmeryar\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Accounting\Models\FiscalYear;

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
