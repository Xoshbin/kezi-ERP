<?php

namespace Jmeryar\Sales\Actions\Sales;

use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;

/**
 * Action for cancelling a Quote
 */
class CancelQuoteAction
{
    /**
     * Execute the action to cancel a quote
     */
    public function execute(Quote $quote): Quote
    {
        // Validate quote can be cancelled
        if (! $quote->status->canBeCancelled()) {
            throw new QuoteCannotBeModifiedException(
                __('sales::quote.messages.cancel_validation')
            );
        }

        // Update status
        $quote->update([
            'status' => QuoteStatus::Cancelled,
        ]);

        return $quote->refresh();
    }
}
