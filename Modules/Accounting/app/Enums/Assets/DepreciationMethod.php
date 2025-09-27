<?php

namespace Modules\Accounting\Enums\Assets;

enum DepreciationMethod: string
{
    case StraightLine = 'straight_line';
    case Declining = 'declining';

    /**
     * Get the translated label for the depreciation method.
     */
    public function label(): string
    {
        return __('enums.depreciation_method.'.$this->value);
    }
}
