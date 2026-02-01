<?php

namespace Jmeryar\Payment\Enums\Payments;

enum PaymentType: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    /**
     * Get the translated label for the payment type.
     */
    public function label(): string
    {
        return __('enums.payment_type.'.$this->value);
    }
}
