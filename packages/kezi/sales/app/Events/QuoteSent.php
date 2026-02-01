<?php

namespace Kezi\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kezi\Sales\Models\Quote;

/**
 * Event dispatched when a quote is sent to a customer.
 */
class QuoteSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Quote $quote,
    ) {}
}
