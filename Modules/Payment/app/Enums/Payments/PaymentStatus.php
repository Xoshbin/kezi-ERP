<?php

namespace Modules\Payment\Enums\Payments;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Reconciled = 'reconciled';
    case Canceled = 'canceled';

    /**
     * Get the translated label for the payment status.
     */
    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }
}
