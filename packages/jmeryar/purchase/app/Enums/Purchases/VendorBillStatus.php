<?php

namespace Jmeryar\Purchase\Enums\Purchases;

enum VendorBillStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
    case Paid = 'paid';

    /**
     * Get the translated label for the vendor bill status.
     */
    public function label(): string
    {
        return __('enums.vendor_bill_status.'.$this->value);
    }
}
