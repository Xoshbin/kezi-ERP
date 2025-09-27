<?php

namespace App\Enums\Shared;

enum PaymentState: string
{
    case NotPaid = 'not_paid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return __('enums.payment_state.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::NotPaid => 'gray',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
        };
    }
}
