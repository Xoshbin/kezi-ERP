<?php

namespace Modules\Accounting\Enums\Accounting;

enum TaxType: string
{
    case Sales = 'sales';
    case Purchase = 'purchase';
    case Both = 'both';

    /**
     * Get the translated label for the tax type.
     */
    public function label(): string
    {
        return __('enums.tax_type.'.$this->value);
    }
}
