<?php

namespace Kezi\Accounting\Enums\Accounting;

enum WithholdingTaxApplicability: string
{
    case Services = 'services';
    case Goods = 'goods';
    case Both = 'both';

    /**
     * Get the translated label for display.
     */
    public function label(): string
    {
        return __('enums.withholding_tax_applicability.'.$this->value);
    }
}
