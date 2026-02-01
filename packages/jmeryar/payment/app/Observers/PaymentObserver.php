<?php

namespace Jmeryar\Payment\Observers;

use Jmeryar\Payment\Enums\Payments\PaymentStatus;
use Jmeryar\Payment\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "deleting" event.
     *
     * This acts as a final, non-negotiable guard to prevent the deletion of any
     * payment that is not in a draft state, ensuring the integrity of the audit trail.
     */
    public function deleting(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Draft) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Confirmed payments cannot be deleted. This action is blocked by a system-level integrity rule.'
            );
        }
    }
}
