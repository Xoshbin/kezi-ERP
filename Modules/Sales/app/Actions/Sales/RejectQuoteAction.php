<?php

namespace Modules\Sales\Actions\Sales;

use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Events\QuoteRejected;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;

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
                'Only sent quotes can be rejected.'
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
