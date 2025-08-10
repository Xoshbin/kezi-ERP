<?php

namespace App\Enums\Adjustments;

enum AdjustmentDocumentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    /**
     * Get the translated label for the adjustment document status.
     */
    public function label(): string
    {
        return __('enums.adjustment_document_status.' . $this->value);
    }
}
