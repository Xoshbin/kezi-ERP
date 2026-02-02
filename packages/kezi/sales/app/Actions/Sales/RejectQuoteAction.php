<?php

namespace Kezi\Sales\Actions\Sales;

use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Events\QuoteRejected;
use Kezi\Sales\Exceptions\QuoteCannotBeModifiedException;
use Kezi\Sales\Models\Quote;

/**
 * Action for marking a Quote as rejected by the customer
 */
class RejectQuoteAction
{
    /**
     * Execute the action to reject a quote
     *
     * @param  string|null  $reason  The reason for rejection
     */
    public function execute(Quote $quote, ?string $reason = null): Quote
    {
        // Validate quote can be rejected
        if (! $quote->status->canBeRejected()) {
            throw new QuoteCannotBeModifiedException(
                __('sales::quote.messages.only_sent_can_reject')
            );
        }

        // Update status and reason
        $quote->update([
            'status' => QuoteStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        // Dispatch event
        event(new QuoteRejected($quote));

        return $quote->refresh();
    }
}
