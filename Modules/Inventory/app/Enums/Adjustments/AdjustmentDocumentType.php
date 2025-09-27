<?php

namespace App\Enums\Adjustments;

enum AdjustmentDocumentType: string
{
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case Miscellaneous = 'miscellaneous';

    /**
     * Get the translated label for the adjustment document type.
     */
    public function label(): string
    {
        return __('enums.adjustment_document_type.'.$this->value);
    }
}
