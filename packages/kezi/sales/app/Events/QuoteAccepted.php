<?php

namespace Kezi\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kezi\Sales\Models\Quote;

/**
 * Event dispatched when a quote is accepted by a customer.
 */
class QuoteAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Quote $quote,
    ) {}
}
