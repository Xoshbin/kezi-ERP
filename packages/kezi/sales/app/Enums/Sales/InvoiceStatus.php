<?php

namespace Kezi\Sales\Enums\Sales;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Get the translated label for the invoice status.
     */
    public function label(): string
    {
        return __('enums.invoice_status.'.$this->value);
    }
}
