<?php

namespace Jmeryar\Accounting\Enums\Assets;

enum DepreciationMethod: string
{
    case StraightLine = 'straight_line';
    case Declining = 'declining';
    case SumOfDigits = 'sum_of_digits';

    /**
     * Get the translated label for the depreciation method.
     */
    public function label(): string
    {
        return __('enums.depreciation_method.'.$this->value);
    }
}
