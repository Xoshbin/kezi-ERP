<?php

namespace Modules\Sales\Actions\Sales;

use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;

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
                'Converted or already cancelled quotes cannot be cancelled.'
            );
        }

        // Update status
        $quote->update([
            'status' => QuoteStatus::Cancelled,
        ]);

        return $quote->refresh();
    }
}
