<?php

namespace Jmeryar\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Sales\Models\Quote;

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
