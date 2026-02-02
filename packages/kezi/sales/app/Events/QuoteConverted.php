<?php

namespace Kezi\Sales\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kezi\Sales\Models\Quote;

/**
 * Event dispatched when a quote is converted to a Sales Order or Invoice.
 */
class QuoteConverted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string  $convertedTo  'sales_order' or 'invoice'
     * @param  Model|null  $targetDocument  The created SalesOrder or Invoice
     */
    public function __construct(
        public readonly Quote $quote,
        public readonly string $convertedTo,
        public readonly ?Model $targetDocument = null,
    ) {}
}
